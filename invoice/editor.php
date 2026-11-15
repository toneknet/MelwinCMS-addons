<?php

class invoice_editor
{
    public static function languageScript(): void
    {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        echo '<script type="application/json" id="invoice-language">'
            . json_encode($GLOBALS['LANG']['MODULE']['invoice']['ui'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            . '</script>';
    }
    private static function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

    public static function nextNumber($db, int $company): int
    {
        // Include deleted/credited invoices: an issued number must never be reused.
        $row = $db->o_get_row('invoice', 'SELECT MAX(invoiceno) AS last_number FROM [table] WHERE companyid=' . $company);
        if (!is_array($row) || !array_key_exists('last_number', $row)) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['could_not_read_the_next_invoice_number_no_invoice_has_been_created']));
        return max(1, (int)($row['last_number'] ?? 0) + 1);
    }

    public static function numbered($db, int $company, callable $save): void
    {
        $name = 'invoice:' . substr(hash('sha256', $db->o_prefix('invoice') . ':' . $company), 0, 50);
        $literal = '0x' . bin2hex($name);
        $result = $db->o_get_row('', 'SELECT GET_LOCK(' . $literal . ',5) AS acquired');
        if ((int)($result['acquired'] ?? 0) !== 1) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['an_invoice_is_already_being_saved_try_again_shortly']));
        try { $save(); } finally { $db->o_get_row('', 'SELECT RELEASE_LOCK(' . $literal . ') AS released'); }
    }

    public static function lookup($db, int $company, string $type, string $term): array
    {
        $term = mb_substr(trim($term), 0, 80);
        $literal = $term === '' ? "''" : 'CONVERT(0x' . bin2hex($term) . ' USING utf8mb4)';
        if ($type === 'customers') {
            return array_values($db->o_get_all_ai('customer', 'SELECT id,namn,adress,postnr,postort FROM [table] WHERE deleted=0 AND companyid=' . $company
                . ' AND (INSTR(namn,' . $literal . ')>0 OR INSTR(adress,' . $literal . ')>0) ORDER BY namn LIMIT 30') ?: []);
        }
        if ($type === 'products') {
            return array_values($db->o_get_all_ai('products', 'SELECT id,label,unit,price,tax,connected FROM [table] WHERE deleted=0 AND companyid=' . $company
                . ' AND INSTR(label,' . $literal . ')>0 ORDER BY label LIMIT 30') ?: []);
        }
        throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['unknown_search']));
    }

    public static function submission(array $post, int $company): array
    {
        if (!hash_equals(salt($company . date('Y-m-d'), 30), (string)($post['db_auth_token'] ?? ''))) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_form_has_expired_open_a_new_invoice_and_try_again']));
        $ids = array_filter(array_map('intval', (array)($post['db_select_all_user'] ?? [])), function ($id) { return $id > 0; });
        if (!$ids && (int)($post['db_customer_id'] ?? 0) <= 0) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['find_and_select_a_customer_before_saving_the_invoice']));
        if ($ids) $post['db_select_all_user'] = array_values(array_unique($ids));
        else unset($post['db_select_all_user']);
        foreach (['db_invoicedate','db_dueDate'] as $key) {
            $date = DateTime::createFromFormat('!Y-m-d', (string)($post[$key] ?? ''));
            if (!$date || $date->format('Y-m-d') !== $post[$key]) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['enter_valid_invoice_and_due_dates']));
        }
        $terms = filter_var($post['db_termsOfPayment'] ?? 30, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3650]]);
        if ($terms === false) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['enter_valid_payment_terms_in_days']));
        $post['db_termsOfPayment'] = $terms;
        if (empty($post['db_invoice']) || !is_array($post['db_invoice'])) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['add_at_least_one_invoice_line']));
        $hasProduct = false;
        foreach ($post['db_invoice'] as &$row) {
            if (!is_array($row)) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['invalid_invoice_line']));
            foreach (['product_name','product_text','product_unit','product_connected'] as $key) $row[$key] = (string)($row[$key] ?? '');
            $hasProduct = $hasProduct || trim($row['product_name']) !== '';
            foreach (['product_amount','product_price'] as $key) {
                $value = str_replace([' ', ','], ['', '.'], (string)($row[$key] ?? '0'));
                if ($key === 'product_amount' && $value === 'x' && $row['product_connected'] === 'watermeter') { $row[$key] = 'x'; continue; }
                if (!is_numeric($value)) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['enter_a_valid_quantity_and_price_on_invoice_lines']));
                $row[$key] = (float)$value;
            }
            $row['product_tax'] = (int)($row['product_tax'] ?? 0);
            if (!in_array($row['product_tax'], [0,6,12,25], true)) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['invalid_vat_rate']));
        }
        unset($row);
        if (!$hasProduct) throw new Exception(($GLOBALS['LANG']['MODULE']['invoice']['ui']['add_at_least_one_product_or_service']));
        unset($post['db_invoiceno']); // Display only; the save code always allocates the number.
        return $post;
    }

    public static function render($db, int $company, array $options = []): void
    {
        self::languageScript();
        $next = self::nextNumber($db, $company);
        $today = date('Y-m-d');
        $due = date('Y-m-d', strtotime('+30 days'));
        $customers = $db->o_get_all_ai('customer', 'SELECT id,namn FROM [table] WHERE deleted=0 AND companyid=' . $company . ' ORDER BY namn') ?: [];
        if (isset($options['customers'])) $customers = $options['customers'];
        echo '<form class="invoice-create" action="' . self::esc($options['action'] ?? '?action=save') . '" method="post" data-initial-rows="' . self::esc(json_encode($options['rows'] ?? [], JSON_UNESCAPED_UNICODE)) . '">';
        if (!empty($options['water'])) echo '<input type="hidden" name="watermeter_billing" value="1">';
        echo '<input type="hidden" name="db_auth_token" value="' . self::esc(salt($company . $today,30)) . '">';
        echo '<div class="invoice-create-grid">';
        echo ('<div class="invoice-customer-field"><label for="invoice-customer">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['customer'] . '</label><input id="invoice-customer" type="search" list="invoice-customer-options" autocomplete="off" placeholder="' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['search_by_name_or_address'] . '" required><datalist id="invoice-customer-options"></datalist><input type="hidden" name="db_customer_id" id="invoice-customer-id"><p class="invoice-lookup-status" aria-live="polite"></p></div>');
        echo ('<div><label for="invoice-number">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['next_invoice_number'] . '</label><input id="invoice-number" name="db_invoiceno" value="') . $next . ('" readonly aria-readonly="true"><small>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['the_final_number_is_assigned_automatically_when_the_invoice_is_saved'] . '</small></div>');
        echo ('<div><label for="invoice-date">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['invoice_date'] . '</label><input type="date" id="invoice-date" name="db_invoicedate" value="') . $today . '" required></div>';
        echo ('<div><label for="invoice-terms">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['payment_terms_days'] . '</label><input type="number" id="invoice-terms" name="db_termsOfPayment" value="30" min="0" required></div>');
        echo ('<div><label for="invoice-due">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['due_date'] . '</label><input type="date" id="invoice-due" name="db_dueDate" value="') . $due . '" required></div></div>';
        echo ('<details class="invoice-multiple"><summary>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['select_multiple_customers'] . '</summary><p>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['select_only_the_customers_who_should_each_receive_an_invoice'] . '</p><label><input type="checkbox" id="invoice-select-all"> ' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['select_all'] . '</label><div class="invoice-customer-list">');
        foreach ($customers as $customer) {
            $checked = isset($options['selected']) ? in_array((int)$customer['id'], $options['selected'], true) : !empty($options['water']);
            echo '<label><input type="checkbox" name="db_select_all_user[]" value="' . (int)$customer['id'] . '"' . ($checked ? ' checked' : '') . '> ' . self::esc($customer['namn']) . '</label>';
        }
        echo '</div></details><p class="invoice-multiple-status" hidden></p>';
        echo ('<div class="invoice-rows-scroll"><table class="invoice-create-rows"><thead><tr><th>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['product_service'] . '</th><th>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['quantity'] . '</th><th>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['unit'] . '</th><th>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['price_excl_vat'] . '</th><th>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['vat'] . '</th><th>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['amount_incl_vat'] . '</th><th></th></tr></thead><tbody></tbody></table></div>');
        echo ('<div class="invoice-create-buttons"><button type="button" class="invoice-add-product">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['add_line'] . '</button><button type="button" class="invoice-add-text">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['add_text_line'] . '</button><strong>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['total_incl_vat'] . (' <span class="invoice-create-total">0,00</span>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['currency_suffix'] . '</strong></div><p>') . $GLOBALS['LANG']['MODULE']['invoice']['ui']['the_total_is_rounded_to_whole_sek_when_the_invoice_is_saved'] . '</p>');
        echo ('<p class="invoice-editor-error" role="alert"></p><button type="submit">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['create_invoice'] . '</button></form>');
    }
}
