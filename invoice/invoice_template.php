<?php
/*
 * VIKTIGT!!
 *
 * Nu skall allt som hanteras i fakturan finnas i EN array vid namn $arrInvoice
 *
 * $arrInvoice = array (
 *      'company'   =>  array (...) // Holds company INFO
 *      'customer   =>  array (...) // Holds customer INFO
 *      'invoice'   =>  array (...) // Holds invoice INFO
 *      'rows'      =>  array (...) // Holds rows INFO
 *      'total'     =>  array (...) // Holds total sum INFO
 *
 */

ob_start();
$style_h1 = " style=\"font-size: 2em; text-align:center;  margin-top: 0px;  margin-right: 0px;  margin-bottom: 0px;  margin-left: 0px;  padding-top: 0px;  padding-right: 0px;  padding-bottom: 0px;  padding-left: 0px;  font-weight: normal;  font-family: 'Abel',sans-serif;  color: #47c1f0;\"";
$style_h3 = " style=\"font-size: 1.8em; text-align:center;  margin-top: 0px;  margin-right: 0px;  margin-bottom: 0px;  margin-left: 0px;  padding-top: 0px;  padding-right: 0px;  padding-bottom: 0px;  padding-left: 0px;  font-weight: normal;  font-family: 'Abel',sans-serif;  color: #000;\"";
$style = " style=\"color: black;  font-size: 14px; font-style: normal; font-family: Calibri,sans-serif;  vertical-align: bottom;  white-space: nowrap;\"";
$styleb = " style=\"color: black;  font-size: 14px;  font-weight: 700;  font-style: normal;  font-family: Calibri,sans-serif;  vertical-align: bottom;  white-space: nowrap;\"";
$style_spec=" style=\"color: black;  font-size: 14px;  font-style: normal;  font-family: Calibri,sans-serif; white-space: nowrap; border-top:2px solid #000000;\"";
$style_small = " style=\"color: black;  font-size: 11px; font-style: normal; font-family: Calibri,sans-serif;  vertical-align: bottom;  white-space: nowrap;\"";
$style_small2 = " style=\"color: black;  font-size: 11px; font-style: normal; font-family: Calibri,sans-serif;  vertical-align: bottom;  white-space: nowrap; text-align: center;\"";
$style_message = " style=\"color: black;  font-size: 14px; font-style: normal; font-family: Calibri,sans-serif;  vertical-align: bottom;  white-space: nowrap; border-top:2px solid #000000; border-bottom:2px solid #000000; \"";
$style_box = " style=\"border: 1px solid #DDD;padding: 3px 6px 2px;border-radius: 5px;\"";
$style_box_text_header = " style=\"background-color: #8ed9f6; padding:2px 5px;margin:0px;border-radius: 3px; \""; //display:block;
$style_box_text = " style=\"background-color: #8ed9f6; padding:2px 5px;margin:0px;border-radius: 3px; width:100%;\"";
#$style_th =" style=\"border-bottom:1px solid #000000;\"";
#$style1 = " style=\"color: black;  font-size: 11pt;  font-weight: 700;  font-style: normal;  font-family: Calibri,sans-serif;  vertical-align: bottom;  white-space: nowrap;\"";
$style_speccy_header = " style=\"padding:3px 5px;\"";
$style_speccy_row = " style=\"padding:5px 5px;margin-top:5px;\"";

// 100/ antal columner
$footer_columns = 3;
$footer_width=floor(100/$footer_columns)."%";

// OVAN ÄR INSTÄLLNINGAR

?>
<table width="100%" border="0" cellspacing="2" cellpadding="2" align="center" style="font-family: 'Abel',sans-serif; font-size:14px;"  autosize="1">
<!--<tr>
    <td colspan="2" align="center" valign="middle"><h3 <?=$style_h3;?>><?=$arrInvoice['company']['namn'];?></h3></td>
	<td colspan="2" align="center">      <h1 <?=$style_h1;?>><?=$arrInvoice['title'];?></h1> </td>
</tr>-->
<tr>
	<td width="25%">&nbsp;</td>
	<td width="25%">&nbsp;</td>
	<td width="25%">&nbsp;</td>
	<td width="25%">&nbsp;</td>
</tr>
<tr>
    <td colspan="4" align="left" valign="top" >
        <table  width="100%" border="0" style="font:verdana; font-size:12px;">
            <tr>
                <td align="left" valign="top" ><b>Fakturanr</b></td>
                <td align="left" valign="top" ><?=$arrInvoice['invoice']['invoiceno'];?></td>
                <td align="left" valign="top" ><b>Er referens</b></td>
                <td width="35%" align="left" valign="top" <?=$style_box_text_header;?>><b>Faktureringsadress</b></td>
            </tr>
            <tr>
                <td align="left" valign="top" ><b>Kundnr</b></td>
                <td align="left" valign="top" ><?=$arrInvoice['customer']['id'];?></td>
                <td align="left" valign="top" ><?=$arrInvoice['invoice']['yourRef'];?></td>
                <td rowspan="5" align="left" valign="top" style="font-family: 'Abel',sans-serif; font-size:14px;" >
                    <?=$arrInvoice['customer']['namn'];?><br />
                    <?=$arrInvoice['customer']['adress'];?><br />
                    <?=$arrInvoice['customer']['postnr'];?>&nbsp;&nbsp;<?=$arrInvoice['customer']['postort'];?><br />
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" ><b>Fakturadatum</b></td>
                <td align="left" valign="top" ><?=$arrInvoice['invoice']['invoicedate'];?></td>
                <td align="left" valign="top" ><b>Vår referens</b></td>
            </tr>
            <tr>
                <td align="left" valign="top" ><b>Betalningsvillkor</b></td>
                <td align="left" valign="top" ><?=$arrInvoice['invoice']['termsOfPayment'];?> dagar netto</td>
                <td align="left" valign="top" ><?=$arrInvoice['invoice']['ourRef'];?></td>
            </tr>
            <tr>
                <td align="left" valign="top" ><b>Förfallodatum</b></td>
                <td colspan="2" align="left" valign="top" ><?=$arrInvoice['invoice']['dueDate'];?></td>
            </tr>
            <tr>
                <td colspan="3" align="left" valign="top" >Efter förfallodagen debiteras ränta enligt räntelagen</td>
            </tr>
        </table>
    </td>
</tr>
<tr>
	<td colspan="4" align="center" valign="top" > <!--  height="620px" -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font:Verdana; font-size:14px;">
            <thead>
                <tr <?=$style_box_text;?>>
                    <th align="left" <?=$style_speccy_header;?>>Produkt/Tjänst</th>
                    <th width="20%" align="right" <?=$style_speccy_header;?>>Antal</th>
                    <th width="20%" align="right"  <?=$style_speccy_header;?>>À-pris</th>
                    <th width="20%" align="right"  <?=$style_speccy_header;?>>Belopp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($arrInvoice['rows'] as $arrField) {
                ?>
                <tr>
                    <td<?=$style_speccy_row;?>  align="left"><?=$arrField['product_name'];?></td>
                    <td align="right" valign="top"<?=$style_speccy_row;?>><?=$arrField['product_amount'];?> <?=$arrField['product_unit'];?></td>
                    <td align="right" valign="top"<?=$style_speccy_row;?>><?=fix_number($arrField['product_price']);?></td>
                    <td align="right" valign="top"<?=$style_speccy_row;?>><?=fix_number($arrField['product_total']);?></td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </td>
</tr>
<!--<tr>
	<td colspan="4" align="center" valign="top" >
        <table width="760px" border="0" cellspacing="0" cellpadding="0" style="font:Verdana; font-size:14px;">
            <tbody>
<?php
foreach (getTaxRates() as $key => $value) {
    if ($arrInvoice['total']['tax'.$key]>0) {
    ?>
                <tr>
                    <td>&nbsp;</td>
                    <td align="right" valign="top"><b>Moms <?=$value;?> (beräknad på <?=fix_number($arrInvoice['total']['of'.$key]);?> kr)</b></td>
                    <td align="right" valign="top"><?=fix_number($arrInvoice['total']['tax'.$key]);?> kr</td>
                </tr>
<?php
    }
}
?>
                <tr>
                    <td>&nbsp;</td>
                    <td align="right" valign="top"><b>Öresutjämning</b></td>
                    <td align="right" valign="top"><?=fix_number($arrInvoice['total']['fix']);?> kr</td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td align="right" valign="top" colspan="2" style="font:Verdana; font-size:18px;"><b>Summa att betala:</b>&nbsp;<?=fix_number($arrInvoice['total']['sum']);?> kr</td>
                </tr>

            </tbody>
        </table>
    </td>
</tr>-->
<!--<tr>
	<td colspan="4">
        <table width="760px" border="0" cellspacing="2" cellpadding="2" align="center" style="font:Arial; font-size:14px;"  autosize="1">
            <tr>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Adress</em></b></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Telefon</em></b></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Postgiro</em></b></td>
<?php if ($footer_columns>3) {?>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Internet</em></b></td>
<?php } ?>
            </tr>
            <tr>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['namn'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['phone'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['pg'];?></td>
<?php if ($footer_columns>3) {?>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['www'];?></td>
<?php } ?>
            </tr>
            <tr>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['co'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Företagets säte</em></b></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Bankgiro</em></b></td>
<?php if ($footer_columns>3) {?>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>E-post</em></b></td>
<?php } ?>
            </tr>
            <tr>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['adress'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['sate'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['bg'];?></td>
<?php if ($footer_columns>3) {?>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['email'];?></td>
<?php } ?>
            </tr>
            <tr>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['postnr'];?>&nbsp;<?=$arrInvoice['company']['postort'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Organisationsnr</em></b></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>Bank</em></b></td>
<?php if ($footer_columns>3) {?>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><b><em>&nbsp;</em></b></td>
<?php } ?>
            </tr>
            <tr>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>">&nbsp;</td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['orgnr'];?></td>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>"><?=$arrInvoice['company']['bank'];?></td>
<?php if ($footer_columns>3) {?>
                <td<?php print $style_small;?> width="<?php print $footer_width;?>">&nbsp;</td>
<?php } ?>
            </tr>
        </table>
    </td>
</tr>-->
<!--<tr>
	<td colspan="4" align="center" valign="baseline" <?php print $style_small2;?>> <b>www.friafakturan.se</b> - En gratisalternativ för dig som vill fakturera enkelt</td>
</tr>-->
</table>

<?php

//$mpdf->showImageErrors = true;
//$mpdf->SetDisplayMode('fullpage');
$html = ob_get_contents();
ob_end_clean();
require_once ROOTPATH . 'system/libs/pdf.cls.php';
$mpdf = new pdf();

//        print $html;
if ($strFilename=="") {$strFilename = date("Ymd_his");}
$mpdf->SetDisplayMode('fullpage');
$mpdf->SetHTMLHeader('
<table width="100%">
  <tr>
    <td colspan="2" align="center" valign="middle"><h3 '. $style_h3 . '>' . $arrInvoice['company']['namn'] . '</h3></td>
	  <td colspan="2" align="center"><h1 ' . $style_h1 . '>' . $arrInvoice['title'] . '</h1></td>
  </tr>
</table>');



//Fixa taxes here
$taxes = "";
foreach (getTaxRates() as $key => $value) {
  if ($arrInvoice['total']['tax'.$key]>0) {
        $taxes.= "<tr>\n";
        //$taxes.= "<td>&nbsp;</td>\n";
        $taxes.= "<td align=\"right\" valign=\"top\"><b>Moms " . $value . " (beräknad på " . fix_number($arrInvoice['total']['of'.$key]) . " kr)</b></td>\n";
        $taxes.= "<td align=\"right\" valign=\"top\">" . fix_number($arrInvoice['total']['tax'.$key]) . " kr</td>\n";
        $taxes.= "</tr>\n";
  }
}


// Create QR-code
/*$arrPayment = array (
    "uqr" => 1,
    "tp" => 1,
    "nme" => $arrInvoice['company']['namn'],
    "cid" => $arrInvoice['company']['orgnr'],
    "iref" => $arrInvoice['customer']['id'],
    "idt" => str_replace("-","",$arrInvoice['invoice']['invoicedate']),
    "ddt" => str_replace("-","",$arrInvoice['invoice']['dueDate']),
    "due" => str_replace(",",".",fix_number($arrInvoice['total']['sum'])),
    "pt" => "BG",
    "acc" => $arrInvoice['company']['bg']
);*/

/*echo json_encode($arrPayment);
print "<br /><br />";*/
//include(ROOTPATH . 'resources/plugins/qr/qr.php');

/*$qr = new QRCode();
$qr->addData(json_encode($arrPayment));
$qr->make();
$qr->createImage();*/

//$qr = QRCode::getMinimumQRCode(json_encode($arrPayment), QR_ERROR_CORRECT_LEVEL_L);
//$qr->make();
// Remove 100% on table
/*print "<span id=\"QRCODE\">";
$qr->printHTML();
print "</span>";*/
//$img = $qr->createImage();

//ob_start();
//$image_data = imagepng($img);
//$image_data = ob_get_clean();
//ob_end_clean();

/*var_dump($image_data);*/
/*
' . $qr->printHTML() . '
*/
/*print $GLOBALS['CONFIG']['www-root'] . '/system/modules/invoice/html/qr.php';
$img = file_get_contents(  $GLOBALS['CONFIG']['www-root'] . "/system/modules/invoice/html/qr.php?data" . json_encode($arrPayment)  );
// Encode the image string data into base64
$data = base64_encode($img);

// Display the output
echo $data;
var_dump($img);
?nme=Blackstad%20Vatten%20Ekonomiska%20F\u00f6rening&cid=769631-3142&iref=8&idt=20230402&ddt=20230502&due=0.00&pt=BG&acc=396-7510


<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font:Verdana; font-size:14px;">
    <tbody>
		' . $taxes . '
				<tr>
					<td rowspan="3">' .
					"<img src=\"" . $GLOBALS['CONFIG']['www-root'] . "/system/modules/invoice/html/qr.php?nme=" . $arrInvoice['company']['namn'] .
					"&cid=" . $arrInvoice['company']['orgnr'] .
					"&iref=" . $arrInvoice['invoice']['invoiceno'] .
					"&idt=" . str_replace("-","",$arrInvoice['invoice']['invoicedate']) .
					"&ddt=" . str_replace("-","",$arrInvoice['invoice']['dueDate']) .
					"&due=" . str_replace(",",".",fix_number($arrInvoice['total']['sum'])) .
					"&pt=" . "BG" .
					"&acc=" . $arrInvoice['company']['bg'] .
					"\" width=\"102px\" height=\"102px\">
					" . '</td>
					<td colspan="2">Betala gärna med qr-koden.<br /> Bara att skanna i din bankapp.</td>
				</tr>
        <tr>
            <td align="right" valign="top"><b>Öresutjämning</b></td>
            <td align="right" valign="top">' . fix_number($arrInvoice['total']['fix']) . ' kr</td>
        </tr>
        <tr>
            <td align="right" valign="top" style="font:Verdana; font-size:18px;"><b>Summa att betala</b></td>
            <td align="right" valign="top" style="font:Verdana; font-size:18px;">' . fix_number($arrInvoice['total']['sum']) . ' kr</td>
        </tr>

    </tbody>
</table>



*/
/*print "<img src=\"data:image/png;base64,".base64_encode($image_data) . "\">";*/
// <td align="right" valign="top" colspan="2" style="font:Verdana; font-size:18px;"><b>Summa att betala:</b>&nbsp;' . fix_number($arrInvoice['total']['sum']) . ' kr</td>
// 760px
$mpdf->SetHTMLFooter('
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font:Verdana; font-size:14px;border-top:1px solid black;padding:5px 0px;">
	<tr>
		<td width="50%">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font:Verdana; font-size:14px;">
						<tr>
							<td>' .
							"<img src=\"" . $GLOBALS['CONFIG']['www-root'] . "/system/modules/invoice/html/qr.php?nme=" . $arrInvoice['company']['namn'] .
							"&cid=" . $arrInvoice['company']['orgnr'] .
							"&iref=" . $arrInvoice['invoice']['invoiceno'] .
							"&idt=" . str_replace("-","",$arrInvoice['invoice']['invoicedate']) .
							"&ddt=" . str_replace("-","",$arrInvoice['invoice']['dueDate']) .
							"&due=" . str_replace(",",".",fix_number($arrInvoice['total']['sum'])) .
							"&pt=" . "BG" .
							"&acc=" . $arrInvoice['company']['bg'] .
							"\">
							" . '</td>
							<td>Betala gärna med qr-koden.<br /> Bara att skanna i din bankapp.</td>
						</tr>
		</table>
		</td>
		<td>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font:Verdana; font-size:14px;">
		    <tbody>
				' . $taxes . '
		        <tr>
		            <td align="right" valign="top"><b>Öresutjämning</b></td>
		            <td align="right" valign="top" width="45%">' . fix_number($arrInvoice['total']['fix']) . ' kr</td>
		        </tr>
		        <tr>
		            <td align="right" valign="top" style="font:Verdana; font-size:18px;"><b>Summa att betala</b></td>
		            <td align="right" valign="top" style="font:Verdana; font-size:18px;">' . fix_number($arrInvoice['total']['sum']) . ' kr</td>
		        </tr>
		    </tbody>
		</table>
		</td>
	</tr>
</table>
<table width="100%" border="0" cellspacing="2" cellpadding="2" align="center" style="font:Arial; font-size:14px;border-top:1px solid black;"  autosize="1">
    <tr>
        <td' . $style_small . ' width="25%"><b><em>Adress</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>Telefon</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>Postgiro</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>Internet</em></b></td>
    </tr>
    <tr>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['namn'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['phone'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['pg'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['www'] . '</td>
    </tr>
    <tr>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['co'] . '</td>
        <td' . $style_small . ' width="25%"><b><em>Företagets säte</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>Bankgiro</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>E-post</em></b></td>
    </tr>
    <tr>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['adress'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['sate'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['bg'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['email'] . '</td>
    </tr>
    <tr>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['postnr'] . '&nbsp;' . $arrInvoice['company']['postort'] . '</td>
        <td' . $style_small . ' width="25%"><b><em>Organisationsnr</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>Bank</em></b></td>
        <td' . $style_small . ' width="25%"><b><em>&nbsp;</em></b></td>
    </tr>
    <tr>
        <td' . $style_small . ' width="25%">&nbsp;</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['orgnr'] . '</td>
        <td' . $style_small . ' width="25%">' . $arrInvoice['company']['bank'] . '</td>
        <td' . $style_small . ' width="25%">&nbsp;</td>
    </tr>
</table>
 <p ' . $style_small2 . '><b>www.friafakturan.se</b> - En gratisalternativ för dig som vill fakturera enkelt</p>


');
/*$header="
<html>
<head>
<style>
@page {
  size: auto;
  margin:5px;
  margin-header: 15mm;
  margin-footer: 10mm;
}
</style>
</head>
<body>
";
$footer="</body></html>";*/
$arrAllInvoices[] = $html;
//$mpdf->WriteHTML($header.$html.$footer);
$mpdf->WriteHTML($html);
$filename=ROOTPATH ."pdf/".$strFilename.".pdf";
if (file_exists($filename)) @unlink($filename);
$mpdf->Output($filename,"F");//I //F


/*print "<div>
{$html}
</div>";*/
