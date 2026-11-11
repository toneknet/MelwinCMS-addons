<?php

/** Shared list controls for the module and its DCA callbacks. */
class invoice_view
{
    private static function esc($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function actions(array $row): string
    {
        $pdf = rtrim($GLOBALS['CONFIG']['www-root'], '/') . '/pdf/' . rawurlencode(basename($row['filename'] ?? ''));
        $credit = '?action=credit&id=' . (int)$row['id'] . '&auth_token=' . rawurlencode(salt((int)$row['id'] . date('Y-m-d'), 20));
        return '<span class="invoice-actions"><a href="' . self::esc($pdf) . '" target="_blank" rel="noopener" aria-label="Öppna faktura som PDF">PDF</a>'
            . ' <a href="' . self::esc($credit) . '">Kreditera</a></span>';
    }

    public static function paid(array $row): string
    {
        $id = (int)$row['id'];
        if (!empty($row['paied'])) {
            $url = '?action=unpaid&id=' . $id . '&auth=' . rawurlencode(salt($id, 20));
            return '<a href="' . self::esc($url) . '" class="invoice-status invoice-status-yes invoice-unpay" title="Betald ' . self::esc($row['paied_date'] ?? '') . '. Markera som obetald">&#10003; Ja</a>';
        }
        return '<button type="button" class="invoice-status invoice-payment-open" data-dialog="invoice-payment-' . $id . '">Registrera</button>'
            . '<dialog class="invoice-payment-dialog" id="invoice-payment-' . $id . '" aria-labelledby="invoice-payment-title-' . $id . '">'
            . '<form action="?action=paied" method="post">'
            . '<h3 id="invoice-payment-title-' . $id . '">Registrera betalning</h3>'
            . '<p>Faktura ' . self::esc($row['invoiceno'] ?? $id) . '</p>'
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<input type="hidden" name="authenticity_token" value="' . self::esc(salt($id)) . '">'
            . '<label for="invoice-payment-date-' . $id . '">Betalningsdatum</label>'
            . '<input id="invoice-payment-date-' . $id . '" name="payment[paid_at]" type="date" required max="9999-12-31" value="' . date('Y-m-d') . '">'
            . '<div class="invoice-payment-buttons"><button type="submit">Spara</button>'
            . '<button type="button" class="invoice-payment-cancel">Avbryt</button></div>'
            . '</form></dialog>';
    }

    public static function printed(array $row): string
    {
        $sent = !empty($row['printed']);
        if ($sent) return '<span class="invoice-status invoice-status-yes" title="Skickad ' . self::esc($row['printed_date'] ?? '') . '">&#10003; Ja</span>';
        $url = '?action=sent&id=' . (int)$row['id'] . '&auth_token=' . rawurlencode(salt((int)$row['id'] . date('Y-m-d'), 20));
        return '<a class="invoice-status" href="' . self::esc($url) . '">Markera skickad</a>';
    }
}
