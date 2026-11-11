<?php
/*
Name: Vattenmätare
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
      if (!$this->allowImport) throw new RuntimeException('Du saknar behörighet att lägga till avläsningar.');
      if (!hash_equals(salt('watermeter-manual-' . date('Y-m-d'), 30), (string)($_POST['watermeter_token'] ?? ''))) {
        throw new RuntimeException('Formuläret har löpt ut. Ladda om sidan och försök igen.');
      }
      $pid = filter_var($_POST['estate_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
      $year = filter_var($_POST['year'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => (int)$this->start, 'max_range' => (int)date('Y')]]);
      $reading = trim((string)($_POST['reading'] ?? ''));
      $dateValue = (string)($_POST['datum'] ?? '');
      $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
      if (!$pid || !$year || !preg_match('/^\d{1,20}$/D', $reading) || !$date || $date->format('Y-m-d') !== $dateValue || (int)$date->format('Y') !== $year) {
        throw new RuntimeException('Ange en giltig fastighet, ett datum inom valt år och en mätarställning som heltal (minst 0).');
      }
      $db = db::getInstance();
      // Use the same lock as CSV imports to prevent simultaneous duplicate inserts.
      $lock = '0x' . bin2hex('watermeter:' . substr(hash('sha256', $db->o_prefix('watermeter')), 0, 40));
      $acquired = $db->o_get_row('', "SELECT GET_LOCK($lock,5) AS acquired");
      if ((int)($acquired['acquired'] ?? 0) !== 1) throw new RuntimeException('En avläsning sparas redan. Försök igen om en stund.');
      try {
        $estate = $db->o_get_row('estate', 'SELECT id FROM [table] WHERE id=' . (int)$pid);
        if (!$estate) throw new RuntimeException('Fastigheten finns inte.');
        $existing = $db->o_get_all('watermeter', 'SELECT id FROM [table] WHERE pid=' . (int)$pid
          . " AND datum>='" . $year . "-01-01' AND datum<'" . ($year + 1) . "-01-01'");
        if (!is_array($existing)) throw new RuntimeException('Kunde inte kontrollera befintliga avläsningar.');
        if ($existing) throw new RuntimeException('Fastigheten har redan en avläsning för detta år. Inget skrevs över.');
        if (!$db->o_insert('watermeter', ['pid' => (int)$pid, 'datum' => $dateValue . ' 00:00:00', 'reading' => ltrim($reading, '0') ?: '0'])) {
          throw new RuntimeException('Mätarställningen kunde inte sparas.');
        }
      } finally {
        $db->o_get_row('', "SELECT RELEASE_LOCK($lock) AS released");
      }
      print '<p role="status">Mätarställningen har sparats.</p>';
    } catch (Throwable $error) {
      print '<p role="alert">' . self::esc($error->getMessage()) . '</p>';
    }
  }

  private function importReadings(): void {
    try {
      if (!$this->allowImport) throw new RuntimeException('Du saknar behörighet att importera avläsningar.');
      if (!hash_equals(salt('watermeter-import-' . date('Y-m-d'), 30), (string)($_POST['watermeter_token'] ?? ''))) {
        throw new RuntimeException('Formuläret har löpt ut. Ladda om sidan och försök igen.');
      }
      $file = $_FILES['watermeter_csv'] ?? [];
      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Välj en CSV-fil och kontrollera att uppladdningen lyckades.');
      }
      if (filesize($file['tmp_name']) > 5 * 1024 * 1024) throw new RuntimeException('CSV-filen får vara högst 5 MB.');
      require_once __DIR__ . '/import.php';
      $stream = fopen($file['tmp_name'], 'rb');
      if (!$stream) throw new RuntimeException('Kunde inte öppna CSV-filen.');
      try { $rows = watermeter_import::rows($stream); }
      finally { fclose($stream); }
      $result = watermeter_import::save(db::getInstance(), $rows);
      print '<p role="status">Import klar: ' . $result['inserted'] . ' sparade, ' . $result['skipped'] . ' redan importerade, ' . count($result['errors']) . ' fel.</p>';
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
      print '<p role="alert">Du saknar behörighet att skapa fakturor.</p>';
      return;
    }
    require_once __DIR__ . '/../invoice/invoice.php';
    require_once __DIR__ . '/../invoice/lang/sv.php';
    addCSS('system/modules/invoice/html/invoice.css');
    addJS('system/modules/invoice/html/invoice.js');
    $db = db::getInstance();
    $company = (int)getCompanyID();
    $year = (int)date('Y');
    print '<h2>Vattenfakturor för ' . $year . '</h2><p>En faktura skapas per vald kund. Förbrukningen är skillnaden mellan ' . ($year - 1) . ' och ' . $year . ' års mätarställningar, summerad över kundens fastigheter. Alla extra rader, exempelvis årsavgift, läggs till en gång på varje faktura.</p>';
    print '<p>Ange vattenpris och moms. Behåll vattenradens antal <strong>x</strong> för automatisk förbrukning. Lägg till gemensamma avgifter med <strong>Lägg till rad</strong>. Inga fakturor skapas förrän du trycker på <strong>Skapa faktura</strong>.</p>';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['watermeter_billing'])) {
      try {
        invoice::createWaterInvoices($_POST);
        print '<p role="status">Fakturorna har skapats och finns i fakturamodulen.</p><a href="?">Tillbaka till vattenmätarna</a>';
        return;
      } catch (Throwable $error) {
        print '<p role="alert">' . self::esc($error->getMessage()) . ' Om några fakturor hann sparas före ett tekniskt fel finns de kvar i fakturamodulen.</p>';
      }
    }
    $customers = $db->o_get_all_ai('customer', 'SELECT c.id,c.namn FROM [table] c WHERE c.deleted=0 AND c.companyid=' . $company
      . ' AND EXISTS (SELECT 1 FROM ' . $db->o_prefix('estate') . ' e WHERE e.pid=c.id) ORDER BY c.namn');
    if (!$customers) {
      print '<p>Inga kunder med fastigheter finns i det aktuella företaget.</p>';
      return;
    }
    $rows = [['product_name' => 'Vattenförbrukning ' . ($year - 1) . '–' . $year, 'product_amount' => 'x',
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
      print '<h2>Importera mätarställningar</h2><p>Semikolonseparerad CSV (UTF-8 eller Windows-1252) med kolumnerna Adress;Mätarens serienummer;Datum;Mätarställning. Datum: ÅÅÅÅ-MM-DD. Decimalcomma eller decimalpunkt stöds; mätarställningen avrundas till närmaste heltal (0,5 uppåt). Matchning sker på fastighetens mätarserienummer. Befintliga värden skrivs aldrig över.</p>';
      print '<input type="hidden" name="watermeter_import" value="1">';
      print '<input type="hidden" name="watermeter_token" value="' . self::esc(salt('watermeter-import-' . date('Y-m-d'), 30)) . '">';
      print '<label>CSV-fil <input type="file" name="watermeter_csv" accept=".csv,text/csv" required></label> ';
      print '<button type="submit">Importera avläsningar</button></form>';
    }
    $watmet = $this->getData();
    print "<table>";
    print "<tr>";
    print "<th>Fastighet</th>";
    print "<th>Namn</th>";
    for ($i = $this->start; $i < ($this->start + $this->length); $i++) {
      print "<th>{$i}</th>";
      if ($i > $this->start) {
        print "<th>S:a</th>";
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
          print '<details><summary aria-label="Lägg till mätarställning för ' . self::esc($k) . ' ' . $year . '">' . getIconFull('add') . '</summary>';
          print '<form method="post"><input type="hidden" name="watermeter_manual" value="1">';
          print '<input type="hidden" name="estate_id" value="' . (int)$v['estate_id'] . '">';
          print '<input type="hidden" name="year" value="' . $year . '">';
          print '<input type="hidden" name="watermeter_token" value="' . self::esc(salt('watermeter-manual-' . date('Y-m-d'), 30)) . '">';
          print '<label>Datum <input type="date" name="datum" min="' . $year . '-01-01" max="' . $year . '-12-31" value="' . $defaultDate . '" required></label> ';
          print '<label>Mätarställning <input type="number" name="reading" min="0" step="1" required></label> ';
          print '<button type="submit">Spara</button></form></details>';
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
    print "<th style='text-align:right;'>Total summor:</th>";
    for ($year = $this->start; $year < ($this->start + $this->length); $year++) {

      if ($year > $this->start) {
        $period = ($year - 1) . "-" . $year;
        $totThisYear = $totWat[$period] ?? 0;
        print "<td>";
        print ($totThisYear * 6) . " kr";
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

    if ($this->allowImport) print "<a href='?action=create_invoices' class='button'>Skapa fakturor för innevarande år</a>";

  }
}
