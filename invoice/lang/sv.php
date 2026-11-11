<?php
/* 
 * Language file
 * Module:      Invoice
 * Language:    Svenska  
 */
$GLOBALS['LANG']['MODULE']['invoice']['multiInvoice'] = "Flera mottagare";
$GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_name'] = "Välj en produkt";
$GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_text'] = "Extra information";
$GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_unit'] = "Ex. St/h/kg";
$GLOBALS['LANG']['MODULE']['invoice']['addInvoice'] = "Ny faktura";
$GLOBALS['LANG']['MODULE']['invoice']['addMultiInvoice'] = "Flera fakturor";
$GLOBALS['LANG']['MODULE']['invoice']['addInvoiceTemplate'] = "Skapa faktura efter mall";
$GLOBALS['LANG']['MODULE']['invoice']['overdue'] = "Förfallna: %s kr";
$GLOBALS['LANG']['MODULE']['invoice']['unpaid'] = "Obetalda: %s kr";
$GLOBALS['LANG']['MODULE']['invoice']['ispaid'] = "Betalda: %s kr";
$GLOBALS['LANG']['MODULE']['invoice']['sent'] = "Fakturan har markerats som skickad!";
$GLOBALS['LANG']['MODULE']['invoice']['no_sent'] = "Fakturan har inte markerats som skickad!";
$GLOBALS['LANG']['MODULE']['invoice']['paid'] = "Fakturan har markerats som betald!";
$GLOBALS['LANG']['MODULE']['invoice']['err_paid'] = "Fakturan har inte markerats som betald/obetald!";
$GLOBALS['LANG']['MODULE']['invoice']['no_paid'] = "Fakturan har markerats som obetald!";
$GLOBALS['LANG']['MODULE']['invoice']['title'] = array('Faktura', 'Kredifaktura', '');
$GLOBALS['LANG']['MODULE']['invoice']['invoice_made'] = "Fakturan har skapats med fakturanummer %d. <a href=\"%s/pdf/%s.pdf\">Klicka här för att ladda hem den!</a>";
$GLOBALS['LANG']['MODULE']['invoice']['invoice_made_multi'] = "Flera fakturor har skapats i en fil! <a href=\"%s/pdf/%s.pdf\">Klicka här för att ladda hem den!</a>";
$GLOBALS['LANG']['MODULE']['invoice']['customer'] = array('Kund', 'Välj kund att fakturera');
$GLOBALS['LANG']['MODULE']['invoice']['invoiceno'] = array('Fakturanummer', 'Denna ökas automatiskt');
$GLOBALS['LANG']['MODULE']['invoice']['select_all'] = array('Välj alla kunder', 'Här väljer du om du vill skicka en faktura till varje kund som ser likadan ut');
$GLOBALS['LANG']['MODULE']['invoice']['invoicedate'] = array('Fakturadatum', 'Datum som fakturan skapades');
$GLOBALS['LANG']['MODULE']['invoice']['yourRef'] = array('Er referens', 'Här anges er referens');
$GLOBALS['LANG']['MODULE']['invoice']['termsOfPayment'] = array('Betalningsvillkor', 'Dagar');
$GLOBALS['LANG']['MODULE']['invoice']['ourRef'] = array('Vår referens', 'Här anges vår referens');
$GLOBALS['LANG']['MODULE']['invoice']['dueDate'] = array('Förfallodatum', 'Här anges förfallodatum');
$GLOBALS['LANG']['MODULE']['invoice']['sum'] = array('Totalsumma', '');
$GLOBALS['LANG']['MODULE']['invoice']['paied'] = array('Betald', '');
$GLOBALS['LANG']['MODULE']['invoice']['paied_date'] = array('Betalningsdatum', '');
$GLOBALS['LANG']['MODULE']['invoice']['printed'] = array('Skickad', '');
$GLOBALS['LANG']['MODULE']['invoice']['printed_date'] = array('Utskriftsdatum', '');
$GLOBALS['LANG']['MODULE']['invoice']['cancelled'] = array('Makulerad', '');
$GLOBALS['LANG']['MODULE']['invoice']['filename'] = array('', '');
$GLOBALS['LANG']['MODULE']['invoice']['visible'] = array('Synlig', '');
$GLOBALS['LANG']['MODULE']['invoice']['customer_id'] = array('Kundid', '');
$GLOBALS['LANG']['MODULE']['invoice']['invoice_credit'] = "En kreditfaktura har skapats med fakturanummer %d. <a href=\"%s/pdf/%s.pdf\">Klicka här för att ladda hem den!</a>";
$GLOBALS['LANG']['MODULE']['invoice']['row_product'] = "Produkt / tjänst";
$GLOBALS['LANG']['MODULE']['invoice']['row_text'] = "Fritext";
$GLOBALS['LANG']['MODULE']['invoice']['row_amount'] = "Antal";
$GLOBALS['LANG']['MODULE']['invoice']['row_unit'] = "Enhet";
$GLOBALS['LANG']['MODULE']['invoice']['row_price'] = "Pris";
$GLOBALS['LANG']['MODULE']['invoice']['row_tax'] = "Momssats";
$GLOBALS['LANG']['MODULE']['invoice']['row_total'] = "Deltotal";
$GLOBALS['LANG']['MODULE']['invoice']['icon_print'] = "Skriv ut (PDF)";
$GLOBALS['LANG']['MODULE']['invoice']['icon_tocustomer'] = "Gå till kund";
$GLOBALS['LANG']['MODULE']['invoice']['icon_credit'] = "Kreditera";
$GLOBALS['LANG']['MODULE']['invoice']['icon_send'] = "FUNGERAR INTE!";

$GLOBALS['LANG']['MODULE']['invoice']['currency_list'] = " %s kr";
$GLOBALS['LANG']['MODULE']['invoice']['pay_date'] = "Betaldatum: ";

$GLOBALS['LANG']['MODULE']['invoice']['pay_infobox_when'] = "När betalades fakturan?";
$GLOBALS['LANG']['MODULE']['invoice']['new_row'] = "Ny rad";
$GLOBALS['LANG']['MODULE']['invoice']['new_textrow'] = "Ny text rad";
$GLOBALS['LANG']['MODULE']['invoice']['save'] = "Spara";
$GLOBALS['LANG']['MODULE']['invoice']['pay_infobox_or'] = "…eller";
$GLOBALS['LANG']['MODULE']['invoice']['pay_infobox_cancel'] = "Avbryt";

$GLOBALS['LANG']['MODULE']['invoice']['doNotSave'] = "Inga fakturor kommer att sparas!";

$GLOBALS['LANG']['GENERAL']['selectcustomers'] = "Välj mottagare av fakturorna";
$GLOBALS['LANG']['GENERAL']['selectall'] = "Välj alla";
$GLOBALS['LANG']['GENERAL']['multicustomersselected'] = "Flera kunder valda...";

$GLOBALS['LANG']['MODULE']['invoice']['dblist'][0] = "Förfallna\n<span class=''> %s kr</span>\n<span class=''> varav moms %s kr</span>";
$GLOBALS['LANG']['MODULE']['invoice']['dblist'][1] = "Obetalda\n<span class=''> %s kr</span>\n<span class=''> varav moms %s kr</span>";
$GLOBALS['LANG']['MODULE']['invoice']['dblist'][2] = "Betalda\n<span class=''> %s kr</span>\n<span class=''> varav moms %s kr</span>";
$GLOBALS['LANG']['MODULE']['invoice']['searchcaption'] = 'Sök faktura';
$GLOBALS['LANG']['MODULE']['invoice']['pid'] = ['Överordnad post', ''];
$GLOBALS['LANG']['MODULE']['invoice']['data'] = ['Fakturarader', ''];
