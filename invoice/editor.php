<?php

class invoice_editor
{
    private static function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

    public static function nextNumber($db, int $company): int
    {
        // Include deleted/credited invoices: an issued number must never be reused.
        $row = $db->o_get_row('invoice', 'SELECT MAX(invoiceno) AS last_number FROM [table] WHERE companyid=' . $company);
        if (!is_array($row) || !array_key_exists('last_number', $row)) throw new Exception('Kunde inte läsa nästa fakturanummer. Ingen faktura har skapats.');
        return max(1, (int)($row['last_number'] ?? 0) + 1);
    }

    public static function numbered($db, int $company, callable $save): void
    {
        $name = 'invoice:' . substr(hash('sha256', $db->o_prefix('invoice') . ':' . $company), 0, 50);
        $literal = '0x' . bin2hex($name);
        $result = $db->o_get_row('', 'SELECT GET_LOCK(' . $literal . ',5) AS acquired');
        if ((int)($result['acquired'] ?? 0) !== 1) throw new Exception('En faktura sparas redan. Försök igen om en stund.');
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
        throw new Exception('Okänd sökning.');
    }

    public static function submission(array $post, int $company): array
    {
        if (!hash_equals(salt($company . date('Y-m-d'), 30), (string)($post['db_auth_token'] ?? ''))) throw new Exception('Formuläret har löpt ut. Öppna en ny faktura och försök igen.');
        $ids = array_filter(array_map('intval', (array)($post['db_select_all_user'] ?? [])), function ($id) { return $id > 0; });
        if (!$ids && (int)($post['db_customer_id'] ?? 0) <= 0) throw new Exception('Sök fram och välj en kund innan fakturan sparas.');
        if ($ids) $post['db_select_all_user'] = array_values(array_unique($ids));
        else unset($post['db_select_all_user']);
        foreach (['db_invoicedate','db_dueDate'] as $key) {
            $date = DateTime::createFromFormat('!Y-m-d', (string)($post[$key] ?? ''));
            if (!$date || $date->format('Y-m-d') !== $post[$key]) throw new Exception('Ange giltiga faktura- och förfallodatum.');
        }
        $terms = filter_var($post['db_termsOfPayment'] ?? 30, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3650]]);
        if ($terms === false) throw new Exception('Ange giltiga betalningsvillkor i dagar.');
        $post['db_termsOfPayment'] = $terms;
        if (empty($post['db_invoice']) || !is_array($post['db_invoice'])) throw new Exception('Lägg till minst en fakturarad.');
        $hasProduct = false;
        foreach ($post['db_invoice'] as &$row) {
            if (!is_array($row)) throw new Exception('Ogiltig fakturarad.');
            foreach (['product_name','product_text','product_unit','product_connected'] as $key) $row[$key] = (string)($row[$key] ?? '');
            $hasProduct = $hasProduct || trim($row['product_name']) !== '';
            foreach (['product_amount','product_price'] as $key) {
                $value = str_replace([' ', ','], ['', '.'], (string)($row[$key] ?? '0'));
                if ($key === 'product_amount' && $value === 'x' && $row['product_connected'] === 'watermeter') { $row[$key] = 'x'; continue; }
                if (!is_numeric($value)) throw new Exception('Ange giltigt antal och pris på fakturaraderna.');
                $row[$key] = (float)$value;
            }
            $row['product_tax'] = (int)($row['product_tax'] ?? 0);
            if (!in_array($row['product_tax'], [0,6,12,25], true)) throw new Exception('Ogiltig momssats.');
        }
        unset($row);
        if (!$hasProduct) throw new Exception('Lägg till minst en produkt eller tjänst.');
        unset($post['db_invoiceno']); // Display only; the save code always allocates the number.
        return $post;
    }

    public static function render($db, int $company, array $options = []): void
    {
        $next = self::nextNumber($db, $company);
        $today = date('Y-m-d');
        $due = date('Y-m-d', strtotime('+30 days'));
        $customers = $db->o_get_all_ai('customer', 'SELECT id,namn FROM [table] WHERE deleted=0 AND companyid=' . $company . ' ORDER BY namn') ?: [];
        if (isset($options['customers'])) $customers = $options['customers'];
        echo '<form class="invoice-create" action="' . self::esc($options['action'] ?? '?action=save') . '" method="post" data-initial-rows="' . self::esc(json_encode($options['rows'] ?? [], JSON_UNESCAPED_UNICODE)) . '">';
        if (!empty($options['water'])) echo '<input type="hidden" name="watermeter_billing" value="1">';
        echo '<input type="hidden" name="db_auth_token" value="' . self::esc(salt($company . $today,30)) . '">';
        echo '<div class="invoice-create-grid">';
        echo '<div class="invoice-customer-field"><label for="invoice-customer">Kund</label><input id="invoice-customer" type="search" list="invoice-customer-options" autocomplete="off" placeholder="Sök på namn eller adress" required><datalist id="invoice-customer-options"></datalist><input type="hidden" name="db_customer_id" id="invoice-customer-id"><p class="invoice-lookup-status" aria-live="polite"></p></div>';
        echo '<div><label for="invoice-number">Nästa fakturanummer</label><input id="invoice-number" name="db_invoiceno" value="' . $next . '" readonly aria-readonly="true"><small>Slutligt nummer tilldelas automatiskt när fakturan sparas.</small></div>';
        echo '<div><label for="invoice-date">Fakturadatum</label><input type="date" id="invoice-date" name="db_invoicedate" value="' . $today . '" required></div>';
        echo '<div><label for="invoice-terms">Betalningsvillkor (dagar)</label><input type="number" id="invoice-terms" name="db_termsOfPayment" value="30" min="0" required></div>';
        echo '<div><label for="invoice-due">Förfallodatum</label><input type="date" id="invoice-due" name="db_dueDate" value="' . $due . '" required></div></div>';
        echo '<details class="invoice-multiple"><summary>Välj flera kunder</summary><p>Markera bara de kunder som ska få varsin faktura.</p><label><input type="checkbox" id="invoice-select-all"> Välj alla</label><div class="invoice-customer-list">';
        foreach ($customers as $customer) {
            $checked = isset($options['selected']) ? in_array((int)$customer['id'], $options['selected'], true) : !empty($options['water']);
            echo '<label><input type="checkbox" name="db_select_all_user[]" value="' . (int)$customer['id'] . '"' . ($checked ? ' checked' : '') . '> ' . self::esc($customer['namn']) . '</label>';
        }
        echo '</div></details><p class="invoice-multiple-status" hidden></p>';
        echo '<div class="invoice-rows-scroll"><table class="invoice-create-rows"><thead><tr><th>Produkt / tjänst</th><th>Antal</th><th>Enhet</th><th>Pris exkl. moms</th><th>Moms</th><th>Belopp inkl. moms</th><th></th></tr></thead><tbody></tbody></table></div>';
        echo '<div class="invoice-create-buttons"><button type="button" class="invoice-add-product">Lägg till rad</button><button type="button" class="invoice-add-text">Lägg till textrad</button><strong>Totalt inkl. moms: <span class="invoice-create-total">0,00</span> kr</strong></div><p>Totalsumman avrundas till hela kronor när fakturan sparas.</p>';
        echo '<p class="invoice-editor-error" role="alert"></p><button type="submit">Skapa faktura</button></form>';
    }
}
