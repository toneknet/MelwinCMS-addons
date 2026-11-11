<?php
// Simple ERROR check
$ok = 0;
$tot = 0;
// Kolla att alla fält är satta
foreach ($_GET as $key => $value) {
  $tot++;
  if(in_array($key, array('nme','cid','iref','idt','ddt','due','pt','acc')))  {
    $ok++;
    ${$key} = $value;
  }
}
if ($ok !== $tot) {
  die('');
}
// Create data for QR CODE
$arrPayment = array (
    "uqr" => 1,
    "tp" => 1,
    "nme" => $nme,
    "cid" => $cid,
    "iref" => $iref,
    "idt" => $idt,
    "ddt" => $ddt,
    "due" => $due,
    "pt" => $pt,
    "acc" => $acc
);
// Load qr class
$root = dirname(__DIR__, 4);
require_once $root . '/system/libs/dependencies.cls.php';
(new dependencies($root . '/resources/plugins', $root . '/system/modules'))->load('qr');
// Start generate qr code
$qr = QRCode::getMinimumQRCode(json_encode($arrPayment), QR_ERROR_CORRECT_LEVEL_L);
$img = $qr->createImage();
/*// Load an image from PNG URL
$im = imagecreatefrompng(
'https://media.geeksforgeeks.org/wp-content/uploads/geeksforgeeks-13.png');
*/
// View the loaded image in browser using imagepng() function
header('Content-type: image/png');
imagepng($img);
imagedestroy($img);
?>
