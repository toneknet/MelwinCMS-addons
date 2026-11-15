<?php

class invoice_company_settings
{
    public const FIELDS = [
        'ourRef' => 'Our reference', 'namn' => 'Company name', 'co' => 'C/o',
        'adress' => 'Address', 'postnr' => 'Postal code', 'postort' => 'City',
        'phone' => 'Phone', 'pg' => 'Postal giro', 'www' => 'Website',
        'sate' => 'Registered office', 'bg' => 'Bank giro', 'email' => 'Email',
        'orgnr' => 'Organisation number', 'bank' => 'Bank',
    ];

    public static function labels(): array
    {
        return array_replace(self::FIELDS, $GLOBALS['LANG']['MODULE']['invoice']['company_fields'] ?? []);
    }

    private static function esc($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function notify(string $type, string $message): void
    {
        $text = self::esc($message);
        $category = $type === 's' ? 'success' : 'warning';
        if (class_exists('alertbox')) {
            $before = count($_SESSION['message'][$category] ?? []);
            alertbox::add($type, $text);
            if (count($_SESSION['message'][$category] ?? []) > $before) return;
            // Older alertbox versions reject short type codes in add().
            if (is_callable(['alertbox', 'showBoxDirect'])) {
                echo alertbox::showBoxDirect($category, $text);
                return;
            }
        }
        echo '<p role="' . ($type === 's' ? 'status' : 'alert') . '">' . $text . '</p>';
    }

    private static function settings(array $company): array
    {
        $raw = $company['settings'] ?? '';
        if (is_array($raw)) return $raw;
        if ($raw === '' || $raw === null) return [];
        $settings = @unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($settings)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_existing_company_settings_could_not_be_read_they_have_not_been_o']));
        return $settings;
    }

    public static function hydrate(array $company): array
    {
        $settings = self::settings($company);
        // Read the former nested storage for compatibility, then prefer CMS fields.
        $module = $settings['module']['invoice'] ?? [];
        $saved = is_array($module) ? ($module['company'] ?? []) : [];
        if (is_array($module)) {
            $saved = array_replace(is_array($saved) ? $saved : [], array_intersect_key($module, self::FIELDS));
        }
        if (is_array($saved)) {
            foreach (self::labels() as $field => $label) {
                if (isset($saved[$field]) && is_string($saved[$field])) $company[$field] = $saved[$field];
            }
        }
        return $company;
    }

    public static function save($db, int $company, array $post, bool $canEdit): array
    {
        if (!$canEdit) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['you_do_not_have_permission_to_change_company_settings']));
        if (!hash_equals(salt('invoice-company-' . $company . '-' . date('Y-m-d'), 30), (string)($post['company_token'] ?? ''))) {
            throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_form_has_expired_reload_the_page_and_try_again']));
        }
        $values = [];
        foreach (self::labels() as $field => $label) {
            $raw = $post[$field] ?? '';
            if (!is_string($raw)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['invalid_value_for']) . $label . '.');
            $value = trim($raw);
            if (mb_strlen($value) > 250 || preg_match('/[\x00-\x1F\x7F]/', $value)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['invalid_value_for']) . $label . '.');
            $values[$field] = $value;
        }
        if ($values['namn'] === '') throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['enter_a_company_name']));
        if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['enter_a_valid_email_address']));
        self::invoiceFilename($values['orgnr'], 1);
        $existing = $db->o_get_row('company', 'SELECT * FROM [table] WHERE deleted=0 AND id=' . $company);
        if (!$existing) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_active_company_could_not_be_found']));
        $settings = self::settings($existing);
        // Match the CMS config/settings.php convention: module -> invoice -> field.
        foreach ($values as $field => $value) $settings['module']['invoice'][$field] = $value;
        $stored = ['settings' => serialize($settings)];
        // The legacy o_update interpolates raw strings. Hex literals also handle
        // apostrophes and backslashes without depending on SQL escaping modes.
        $updates = [];
        foreach ($stored as $field => $value) {
            $literal = $value === '' ? "''" : 'CONVERT(0x' . bin2hex($value) . ' USING utf8mb4)';
            $updates[] = '`' . $field . '`=' . $literal;
        }
        $table = '`' . str_replace('`', '``', $db->o_prefix('company')) . '`';
        if (!$db->o_query('UPDATE ' . $table . ' SET ' . implode(',', $updates) . ' WHERE id=' . $company . ' AND deleted=0')) {
            throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['company_details_could_not_be_saved']));
        }
        $saved = $db->o_get_row('company', 'SELECT * FROM [table] WHERE deleted=0 AND id=' . $company);
        if (is_array($saved)) $saved = self::hydrate($saved);
        foreach ($values as $field => $value) {
            if (!is_array($saved) || !array_key_exists($field, $saved) || (string)$saved[$field] !== $value) {
                throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_database_save_could_not_be_confirmed_check_the_company_details_a']));
            }
        }
        if (class_exists('settings') && property_exists('settings', 'companySettings') && (int)getCompanyID() === $company) {
            settings::$companySettings = $settings;
        }
        return $values;
    }

    public static function render($db, int $company, bool $canEdit): void
    {
        if (!$canEdit) {
            self::notify('w', ($GLOBALS['LANG']['MODULE']['invoice']['ui']['you_do_not_have_permission_to_change_company_settings']));
            return;
        }
        $values = $db->o_get_row('company', 'SELECT * FROM [table] WHERE deleted=0 AND id=' . $company);
        if (!$values) { self::notify('w', ($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_company_could_not_be_found'])); return; }
        try { $values = self::hydrate($values); }
        catch (Throwable $error) { self::notify('w', $error->getMessage()); return; }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            try {
                $values = array_replace($values, self::save($db, $company, $_POST, $canEdit));
                self::notify('s', ($GLOBALS['LANG']['MODULE']['invoice']['ui']['company_details_have_been_saved']));
            } catch (Throwable $error) {
                foreach (self::labels() as $field => $label) {
                    if (isset($_POST[$field]) && is_string($_POST[$field])) $values[$field] = $_POST[$field];
                }
                self::notify('w', $error->getMessage());
            }
        }
        echo ('<h2>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['company_settings_for_invoicing'] . '</h2><p>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['these_settings_apply_to_the_active_company_and_are_used_on_new_invoi'] . '</p>');
        echo '<form method="post"><input type="hidden" name="invoice_company_settings" value="1"><div class="invoice-create-grid">';
        echo '<input type="hidden" name="company_token" value="' . self::esc(salt('invoice-company-' . $company . '-' . date('Y-m-d'), 30)) . '">';
        foreach (self::labels() as $field => $label) {
            echo '<div><label for="company-' . $field . '">' . $label . '</label><input id="company-' . $field . '" name="' . $field
                . '" type="' . ($field === 'email' ? 'email' : 'text') . '" maxlength="250" value="' . self::esc($values[$field] ?? '') . '"'
                . (in_array($field, ['namn', 'orgnr'], true) ? ' required' : '') . '></div>';
        }
        echo ('</div><p>' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['pdf_filenames_use_the_organisation_number_followed_by_an_invoice_num'] . '</p><button type="submit">' . $GLOBALS['LANG']['MODULE']['invoice']['ui']['save_company_details'] . '</button></form>');
    }

    public static function invoiceFilename(string $orgnr, $number): string
    {
        $orgnr = preg_replace('/\s+/', '', $orgnr);
        if (!preg_match('/^\d[\d-]{0,29}$/D', $orgnr)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['enter_an_organisation_number_using_digits_and_optional_hyphens_in_co']));
        $number = (string)$number;
        if (!preg_match('/^[1-9]\d*$/D', $number)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['invalid_invoice_number']));
        return $orgnr . '-' . str_pad($number, max(10, strlen($number) + 1), '0', STR_PAD_LEFT);
    }

    public static function batchFilename(string $name, string $timestamp): string
    {
        $name = strtr($name, ['å'=>'a', 'ä'=>'a', 'ö'=>'o', 'Å'=>'a', 'Ä'=>'a', 'Ö'=>'o']);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii === false ? $name : $ascii)), '-');
        return substr($slug === '' ? 'foretag' : $slug, 0, 100) . '-samlingsfakturor-' . $timestamp;
    }
}
