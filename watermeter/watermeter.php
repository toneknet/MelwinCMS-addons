<?php
/*
Name: Water meters
Version: 1.0.0
Type: Module
Requires: invoice
*/
class watermeter
{
  public $start = '2022';
  public $length = null;
  private $allowImport = false;

  function __construct($dbSettings = [])
  {
    $this->allowImport = !empty($dbSettings['own_access']['edit']);
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['watermeter_import'])) {
      $this->importReadings();
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['watermeter_manual'])) {
      $this->saveManualReading();
    }
    $this->length = date("Y") + 1 - $this->start;


    $a = input::_get('action');
    if ($a == 'create_invoices') {
      $this->create_invoices();
    } else {
      $this->list();
    }

    
  }

  private static function esc($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  private function saveManualReading(): void {
    try {
      if (!$this->allowImport) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['you_do_not_have_permission_to_add_meter_readings']));
      if (!hash_equals(salt('watermeter-manual-' . date('Y-m-d'), 30), (string)($_POST['watermeter_token'] ?? ''))) {
        throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_form_has_expired_reload_the_page_and_try_again']));
      }
      $pid = filter_var($_POST['estate_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
      $year = filter_var($_POST['year'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => (int)$this->start, 'max_range' => (int)date('Y')]]);
      $reading = trim((string)($_POST['reading'] ?? ''));
      $dateValue = (string)($_POST['datum'] ?? '');
      $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
      if (!$pid || !$year || !preg_match('/^\d{1,20}$/D', $reading) || !$date || $date->format('Y-m-d') !== $dateValue || (int)$date->format('Y') !== $year) {
        throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['enter_a_valid_property_a_date_within_the_selected_year_and_a_whole_n']));
      }
      $db = db::getInstance();
      // Use the same lock as CSV imports to prevent simultaneous duplicate inserts.
      $lock = '0x' . bin2hex('watermeter:' . substr(hash('sha256', $db->o_prefix('watermeter')), 0, 40));
      $acquired = $db->o_get_row('', "SELECT GET_LOCK($lock,5) AS acquired");
      if ((int)($acquired['acquired'] ?? 0) !== 1) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['a_meter_reading_is_already_being_saved_try_again_shortly']));
      try {
        $estate = $db->o_get_row('estate', 'SELECT id FROM [table] WHERE id=' . (int)$pid);
        if (!$estate) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_property_does_not_exist']));
        $existing = $db->o_get_all('watermeter', 'SELECT id FROM [table] WHERE pid=' . (int)$pid
          . " AND datum>='" . $year . "-01-01' AND datum<'" . ($year + 1) . "-01-01'");
        if (!is_array($existing)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['could_not_check_existing_meter_readings']));
        if ($existing) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_property_already_has_a_reading_for_this_year_nothing_was_overwri']));
        if (!$db->o_insert('watermeter', ['pid' => (int)$pid, 'datum' => $dateValue . ' 00:00:00', 'reading' => ltrim($reading, '0') ?: '0'])) {
          throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_meter_reading_could_not_be_saved']));
        }
      } finally {
        $db->o_get_row('', "SELECT RELEASE_LOCK($lock) AS released");
      }
      print ('<p role="status">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_meter_reading_has_been_saved'] . '</p>');
    } catch (Throwable $error) {
      print '<p role="alert">' . self::esc($error->getMessage()) . '</p>';
    }
  }

  private function importReadings(): void {
    try {
      if (!$this->allowImport) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['you_do_not_have_permission_to_import_meter_readings']));
      if (!hash_equals(salt('watermeter-import-' . date('Y-m-d'), 30), (string)($_POST['watermeter_token'] ?? ''))) {
        throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_form_has_expired_reload_the_page_and_try_again']));
      }
      $file = $_FILES['watermeter_csv'] ?? [];
      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['choose_a_csv_file_and_check_that_the_upload_succeeded']));
      }
      if (filesize($file['tmp_name']) > 5 * 1024 * 1024) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_csv_file_must_not_exceed_5_mb']));
      require_once __DIR__ . '/import.php';
      $stream = fopen($file['tmp_name'], 'rb');
      if (!$stream) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['watermeter']['ui']['could_not_open_the_csv_file']));
      try { $rows = watermeter_import::rows($stream); }
      finally { fclose($stream); }
      $result = watermeter_import::save(db::getInstance(), $rows);
      print ('<p role="status">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['import_complete']) . $result['inserted'] . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['saved']) . $result['skipped'] . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['already_imported']) . count($result['errors']) . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['errors'] . '</p>');
      if ($result['errors']) {
        print '<ul>';
        foreach ($result['errors'] as $error) print '<li>' . self::esc($error) . '</li>';
        print '</ul>';
      }
    } catch (Throwable $error) {
      print '<p role="alert">' . self::esc($error->getMessage()) . '</p>';
    }
  }

  function create_invoices() {
    if (!$this->allowImport) {
      print ('<p role="alert">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['you_do_not_have_permission_to_create_invoices'] . '</p>');
      return;
    }
    require_once __DIR__ . '/../invoice/invoice.php';
    addCSS('system/modules/invoice/html/invoice.css');
    addJS('system/modules/invoice/html/invoice.js');
    $db = db::getInstance();
    $company = (int)getCompanyID();
    $year = (int)date('Y');
    print ('<h2>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['water_invoices_for']) . $year . ('</h2><p>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['one_invoice_is_created_per_selected_customer_consumption_is_the_diff']) . ($year - 1) . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['and']) . $year . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['annual_meter_readings_totalled_across_the_customer_properties_all_ex'] . '</p>');
    print ('<p>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['enter_the_water_price_and_vat_keep_the_water_quantity_as_strong_x_st'] . '</p>');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['watermeter_billing'])) {
      try {
        $batchPdf = invoice::createWaterInvoices($_POST);
        if ($batchPdf !== null) {
          $url = rtrim($GLOBALS['CONFIG']['www-root'], '/') . '/pdf/' . rawurlencode($batchPdf);
          print '<p><a class="button" href="' . self::esc($url) . ('" target="_blank" rel="noopener">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['open_all_invoices_in_one_pdf_for_printing'] . '</a></p>');
        }
        print ('<p role="status">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['the_invoices_have_been_created_and_are_available_in_the_invoice_modu'] . '</p><a href="?">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['back_to_water_meters'] . '</a>');
        return;
      } catch (Throwable $error) {
        print '<p role="alert">' . self::esc($error->getMessage()) . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['any_invoices_saved_before_a_technical_error_remain_in_the_invoice_mo'] . '</p>');
      }
    }
    $customers = $db->o_get_all_ai('customer', 'SELECT c.id,c.namn FROM [table] c WHERE c.deleted=0 AND c.companyid=' . $company
      . ' AND EXISTS (SELECT 1 FROM ' . $db->o_prefix('estate') . ' e WHERE e.pid=c.id) ORDER BY c.namn');
    if (!$customers) {
      print ('<p>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['no_customers_with_properties_were_found_in_the_current_company'] . '</p>');
      return;
    }
    $rows = [['product_name' => ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['water_consumption']) . ($year - 1) . '–' . $year, 'product_amount' => 'x',
      'product_unit' => 'm³', 'product_price' => '6', 'product_tax' => '0', 'product_connected' => 'watermeter']];
    if (isset($_POST['db_invoice']) && is_array($_POST['db_invoice'])) $rows = array_values($_POST['db_invoice']);
    $options = ['water' => true, 'action' => '?action=create_invoices', 'customers' => $customers, 'rows' => $rows];
    if (isset($_POST['watermeter_billing'])) $options['selected'] = array_map('intval', (array)($_POST['db_select_all_user'] ?? []));
    invoice_editor::render($db, $company, $options);
  }

  function getData() {
    $db = db::getInstance();
    $house = $db->o_get_all("estate", "SELECT e.*,c.namn FROM [table] as e LEFT JOIN " . $db->o_prefix('customer') . " as c ON c.id=e.pid AND c.deleted=0", 'adress');   // ,c.*,e.id as id,e.pid as pid
    $water = $db->o_get_all("watermeter", "SELECT id,pid, date(datum) as datum,reading FROM [table] ORDER BY pid, datum");
    $watmet = [];
    foreach ($house as $k => $v) {
      $id = $v['id'];
      $watmet[$k]['estate_id'] = $id;
      $watmet[$k]['namn'] = $v['namn'];
      for ($year = $this->start; $year < ($this->start + $this->length); $year++) {
        $ans = (array_filter($water, function ($elem) use ($id, $year) {
          return $elem['pid'] == $id && str_starts_with($elem['datum'], $year);
        }));
        $reading = current($ans)['reading'] ?? '';
        $watmet[$k][$year] = $reading === '' ? '' : (int)round((float)str_replace(',', '.', $reading));
      }
    }
    foreach ($watmet as $h => $w) {
      for ($year = ($this->start + 1); $year < ($this->start + $this->length); $year++) {
        $tot = (int)$w[$year] - (int)$w[$year - 1];
        $watmet[$h][($year - 1) . "-" . $year] = ($w[$year] !== '' && $w[$year - 1] !== '' && $tot >= 0) ? $tot : "";
      }

    }
    return (array) $watmet;
  }

  function list() {
    if ($this->allowImport) {
      print '<form method="post" enctype="multipart/form-data">';
      print ('<h2>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['import_meter_readings'] . '</h2><p>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['semicolon_separated_csv_utf_8_or_windows_1252_with_the_fixed_columns'] . '</p>');
      print '<input type="hidden" name="watermeter_import" value="1">';
      print '<input type="hidden" name="watermeter_token" value="' . self::esc(salt('watermeter-import-' . date('Y-m-d'), 30)) . '">';
      print ('<label>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['csv_file'] . ' <input type="file" name="watermeter_csv" accept=".csv,text/csv" required></label> ');
      print ('<button type="submit">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['import_readings'] . '</button></form>');
    }
    $watmet = $this->getData();
    print "<table>";
    print "<tr>";
    print ('<th>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['property'] . '</th>');
    print ('<th>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['name'] . '</th>');
    for ($i = $this->start; $i < ($this->start + $this->length); $i++) {
      print "<th>{$i}</th>";
      if ($i > $this->start) {
        print ('<th>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['total'] . '</th>');
      }
    }



    print "</tr>";
    $totWat = [];
    foreach ($watmet as $k => $v) {
      print "<tr>";
      print "<th>" . self::esc($k) . "</th>";
      print "<td>" . self::esc($v['namn']) . "</td>";

      for ($year = $this->start; $year < ($this->start + $this->length); $year++) {
        print "<td>";
        if ($v[$year] !== '') {
          print self::esc($v[$year]);
        } elseif ($this->allowImport) {
          $defaultDate = $year == date('Y') ? date('Y-m-d') : $year . '-12-31';
          print ('<details><summary aria-label="' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['add_meter_reading_for']) . self::esc($k) . ' ' . $year . '">' . getIconFull('add') . '</summary>';
          print '<form method="post"><input type="hidden" name="watermeter_manual" value="1">';
          print '<input type="hidden" name="estate_id" value="' . (int)$v['estate_id'] . '">';
          print '<input type="hidden" name="year" value="' . $year . '">';
          print '<input type="hidden" name="watermeter_token" value="' . self::esc(salt('watermeter-manual-' . date('Y-m-d'), 30)) . '">';
          print ('<label>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['date'] . ' <input type="date" name="datum" min="') . $year . '-01-01" max="' . $year . '-12-31" value="' . $defaultDate . '" required></label> ';
          print ('<label>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['meter_reading'] . ' <input type="number" name="reading" min="0" step="1" required></label> ');
          print ('<button type="submit">' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['save'] . '</button></form></details>');
        } else {
          print '&mdash;';
        }
        print "</td>";
        if ($year > $this->start) {
          print "<th>";
          $period = ($year - 1) . "-" . $year;
          print $v[$period] ?? "";
          if (!isset($totWat[$period])) $totWat[$period] = 0;
          $totWat[$period] += (int)$v[$period];
          print "</th>";
        }
      }



      print "</tr>";
    }
    print "<tr>";
    print "<th></th>";
    print ('<th style=\'text-align:right;\'>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['totals'] . '</th>');
    for ($year = $this->start; $year < ($this->start + $this->length); $year++) {

      if ($year > $this->start) {
        $period = ($year - 1) . "-" . $year;
        $totThisYear = $totWat[$period] ?? 0;
        print "<td>";
        print ($totThisYear * 6) . ($GLOBALS['LANG']['MODULE']['watermeter']['ui']['currency_suffix']);
        print "</td>";
        print "<th>";
        print $totThisYear;
        print "</th>";
      } else {
        print "<td>&nbsp;</td>";
      }
    }
    print "</tr>";


    print "</table>";

    if ($this->allowImport) print ('<a href=\'?action=create_invoices\' class=\'button\'>' . $GLOBALS['LANG']['MODULE']['watermeter']['ui']['create_invoices_for_the_current_year'] . '</a>');

  }
}
