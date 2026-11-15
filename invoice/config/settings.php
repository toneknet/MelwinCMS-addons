<?php

// Registered by the CMS company settings page. Keep the invoice shortcut in sync
// by sharing the field definitions and storing values at module/invoice/<field>.
require_once dirname(__DIR__) . '/company_settings.php';

foreach (invoice_company_settings::labels() as $field => $label) {
    $GLOBALS['MODULE']['invoice']['settings'][$field] = [
        'type' => 'varchar',
        'field' => 'invoice/' . $field,
        'caption' => [$label, ($GLOBALS['LANG']['MODULE']['invoice']['ui']['used_on_the_company_invoices'])],
        'required' => in_array($field, ['namn', 'orgnr'], true),
    ];
    // Older form evaluators treat even eval.required=false as required.
    if (in_array($field, ['namn', 'orgnr'], true)) {
        $GLOBALS['MODULE']['invoice']['settings'][$field]['eval']['required'] = true;
    }
}
