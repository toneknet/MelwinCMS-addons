<?php
// Only the authenticated central AJAX dispatcher may execute this endpoint.
if (!defined('MODPATH') || !isset($auth) || !$auth->isAuthed()) { http_response_code(403); exit; }
$input = new input();
header('Content-Type: text/html; charset=utf-8');
$strType = $input->get('type');
if (in_array($strType, ['lookup_customers', 'lookup_products'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        require_once __DIR__ . '/editor.php';
        $term = isset($_GET['term']) && is_string($_GET['term']) ? $_GET['term'] : '';
        $type = $strType === 'lookup_customers' ? 'customers' : 'products';
        echo json_encode(['success' => true, 'items' => invoice_editor::lookup(db::getInstance(), (int)getCompanyID(), $type, $term)], JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Kunde inte söka i registret.']);
    }
    exit;
}


if ($strType == 'new_textrow') {
    $intLastId = intval($input->get('lastid'));
    print "<tr class=\"product_row\"><td colspan=\"6\"><textarea class=\"autogrow product_text\" id=\"db_invoice_" . $intLastId . "_product_text\" maxlength=\"255\" name=\"db_invoice[" . $intLastId . "][product_text]\" style=\"height: 52px;\"></textarea><span class=\"counter\" data-maxlength=\"255\">0/255</span></td><td></td></tr>";
    exit;
}

if ($strType == 'new_productrow') {
    $intLastId = intval($input->get('lastid'));
    print "<tr class=\"product_row\">";
    print "<td>	<input autocomplete=\"off\" class=\"product_name ui-autocomplete-input\" data-autocomplete=\"/test.php\" data-id=\"" . $intLastId . "\" id=\"invoice_" . $intLastId . "_product_name\" name=\"db_invoice[" . $intLastId . "][product_name]\" placeholder=\"Välj en produkt\" size=\"20\" type=\"text\" value=\"\">
    <input type=\"hidden\" id=\"db_invoice_" . $intLastId . "_product_connected\" name=\"db_invoice[" . $intLastId . "][product_connected]\">
    <input type=\"hidden\" id=\"db_invoice_" . $intLastId . "_product_id\" name=\"db_invoice[" . $intLastId . "][product_id]\">



</td><td></td><td>	<input autocomplete=\"off\" class=\"product_amount\" id=\"db_invoice_" . $intLastId . "_product_amount\" name=\"db_invoice[" . $intLastId . "][product_amount]\" size=\"5\" type=\"text\" value=\"\">
</td><td>	<input autocomplete=\"off\" class=\"product_unit\" id=\"db_invoice_" . $intLastId . "_product_unit\" name=\"db_invoice[" . $intLastId . "][product_unit]\" placeholder=\"Ex. St/h/kg\" size=\"8\" type=\"text\" value=\"\">
</td><td>	<input autocomplete=\"off\" class=\"product_price\" id=\"db_invoice_" . $intLastId . "_product_price\" name=\"db_invoice[" . $intLastId . "][product_price]\" size=\"8\" type=\"text\" value=\"\">
</td><td>	<select name=\"db_invoice[" . $intLastId . "][product_tax]\" class=\"product_tax frm_invoice\" id=\"db_invoice_" . $intLastId . "_product_tax\" value=\"0\">
		<option value=\"0\" selected=\"\">0%</option>
		<option value=\"6\">6%</option>
		<option value=\"12\">12%</option>
		<option value=\"25\">25%</option>
	</select>
</td><td class=\"total\"></td></tr>";
    exit;
}

