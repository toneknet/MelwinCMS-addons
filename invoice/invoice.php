<?php
require_once __DIR__ . "/view.php";
require_once __DIR__ . "/editor.php";
require_once __DIR__ . '/water_usage.php';
/*
Name: Fakturering
Version: 1.0.2
Type: Module
Requires: customer,products
*/

//addJS('system/modules/invoice/js/invoice.js');

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *
 *
 * Fixa så att Textmeddelandena fungerar med. Kunna använda prefix för olika fält.
 * t.ex. [[customer:fastighet]]
 *
 *
 * saveInvoice() -  Längst ner så blir sparade fakturor vid fler med samma namn
 *                  på alla fakturor. Why? Fixat?
 *
 *
 * 	Produkt / tjänst,	Fritext,	Antal,	Enhet,	Pris exkl.,	Moms,	Rabatt,	Deltotal exkl.
 * 
 * 
 * 
 * 
 * Ny faktura       Rapport/export          2025 2024       2025-01-01 - 2025-12-31   sök
 * 
 * Förfallna
 * 6kr
 * varav moms 1 kr
 * 
 * Obetalda
 * 6kr
 * varav moms 1 kr
 * 
 * Betalda	
 * 1080 kr
 * varav moms 216kr
 *
 * #,	Kund,	Summa,	Förfaller,	Betald,	Skickad,	Auto, settings
 *
 *
 *
 *  TODO:
 *  ------
 *
 *
 */
//  ini_set('display_errors', 1);
//  ini_set('display_startup_errors', 1);
//  error_reporting(E_ALL);
//addCSS();
/**
 * Description of invoice
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class invoice
{
    //put your code here
    private $dbFormSettings;

    private $doNotSave = false; // Används när jag INTE vill spara i databasen.
    // Fungerar på ALLA anrop i classen. Sätts till TRUE för att inte spara
    private $SU = false; // SUPERUSER

    function __construct($dispatch = true)
    {
        if (!$dispatch) return;

        addCSS('system/modules/invoice/html/invoice.css');
        addJS('system/modules/invoice/html/invoice.js');
        $input = new input();
        $this->loadSettings();
        $this->SU = (getUserID() == 1) ?? false;
        // if($this->SU) $this->doNotSave=true;
        // print "<pre>";
        // var_dump($this->SU);
        // print "</pre>";

        if ($this->doNotSave) {
            setMsg('w', $GLOBALS['LANG']['MODULE']['invoice']['doNotSave']);
        }

        print "<div class=\"buttonbar\">\n";
        print "<a href=\"?action=add\" class=\"button icon-add\">" . $GLOBALS['LANG']['MODULE']['invoice']['addInvoice'] . "</a>\n";


        print "</div>\n";
        // debug();
        // Spara här
        if (is_array($input->post('db_invoice')) && $input->get('action') == 'save') {
            // pre('SPARA');
            // pre($_POST);
            // return;
            try {
                invoice_editor::numbered(db::getInstance(), (int)getCompanyID(), function () { $this->saveInvoice(); });
            } catch (Throwable $e) { setMsg('e', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')); }
        } elseif ($input->get('action') == 'paied') {
            $this->paiedInvoice();
        } elseif ($input->get('action') == 'unpaid') {
            $this->unpaiedInvoice();
        } elseif ($input->get('action') == 'sent') {
            $this->sentInvoice();
        } elseif ($input->get('action') == 'credit') {
            try {
                invoice_editor::numbered(db::getInstance(), (int)getCompanyID(), function () { $this->creditInvoice(); });
            } catch (Throwable $e) { setMsg('e', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')); }
        } elseif ($input->get('action') == 'send') {
            $this->sendInvoice();
        } elseif ($input->get('action') == 'sendAll') {
            $this->sendAllInvoices();
        }

        if ($input->get('action') == 'add') {
            $this->makeInvoice();
        } else {

            if (isSuperUser()) {
                $this->listInvoices();
                return;
            }
            // List them
            $dblist = new dblist();
            $db = db::getInstance();
            // pre($this->dbFormSettings);

            //            print getCompanyFileName();

            // Förfallna
            $totalOverdueSum = 0;
            $arrOverdueInvoices = $db->o_get_all('invoice', 'SELECT * FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID() . ' AND paied=0 AND dueDate < CURDATE() ORDER BY dueDate ASC');
            if (count($arrOverdueInvoices) > 0) {
                foreach ($arrOverdueInvoices as $value) {
                    $totalOverdueSum = $totalOverdueSum + $value['sum'];
                }
                print "<h4>" .  sprintf($GLOBALS['LANG']['MODULE']['invoice']['overdue'], fix_number($totalOverdueSum)) . "</h4>";
                $dblist->setData($arrOverdueInvoices);
                $dblist->MakeWithSettings($this->dbFormSettings);
            }


            // Obetalda
            $arrUnpaidInvoices = $db->o_get_all('invoice', 'SELECT * FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID() . ' AND paied=0 AND dueDate >= CURDATE() ORDER BY dueDate ASC');
            if (count($arrUnpaidInvoices) > 0) {
                // print "<pre>";
                // print_r($arrUnpaidInvoices);
                $totPossibleToEmail =  array_filter($arrUnpaidInvoices, function ($k, $v) {
                    $cust = unserialize($k['customer']);
                    return ($cust['email'] && !$k['printed']);
                }, ARRAY_FILTER_USE_BOTH);
                // print "</pre>";


                $totalUnpaidSum = 0;
                foreach ($arrUnpaidInvoices as $value) {
                    $totalUnpaidSum = $totalUnpaidSum + $value['sum'];
                }
                if (count($totPossibleToEmail) > 0 && $this->SU) print "<a href=\"?action=sendAll\" class=\"button icon-email\">Det finns fakturor som kan skickas via epost. Klicka här för att skicka samtliga!</a>";
                print "<h4>" .  sprintf($GLOBALS['LANG']['MODULE']['invoice']['unpaid'], fix_number($totalUnpaidSum)) . "</h4>";
                $dblist->setData($arrUnpaidInvoices);
                $dblist->MakeWithSettings($this->dbFormSettings);
            }


            // Betalda
            $arrPaidInvoices = $db->o_get_all('invoice', 'SELECT * FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID() . ' AND paied=1 ORDER BY paied_date DESC'); //AND dueDate >= CURDATE()
            if (count($arrPaidInvoices) > 0) {
                $totalPaidSum = 0;
                foreach ($arrPaidInvoices as $value) {
                    $totalPaidSum = $totalPaidSum + $value['sum'];
                }
                print "<h4>" .  sprintf($GLOBALS['LANG']['MODULE']['invoice']['ispaid'], fix_number($totalPaidSum)) . "</h4>";
                $dblist->setData($arrPaidInvoices);
                // Fix för betalda
                $dbTmpSettings = $this->dbFormSettings;
                $dbTmpSettings['list'] = $dbTmpSettings['list_paied'];
                $dblist->MakeWithSettings($dbTmpSettings);
            }
        }
    }



    function listInvoices() {
        // Load libs/modules needed
        $dblist = new dblist();
        $db = db::getInstance();
        // Get date parts
        $start_date = input::_get('start_date','DATE') ?? date("Y") . "-01-01";
        $end_date = input::_get('end_date','DATE') ?? date("Y") . "-12-31";
        // Get Years
        $arrYears = $db->o_get_all('invoice', 'SELECT year(invoicedate) as year FROM [table] WHERE companyid=' . getCompanyID() . ' GROUP BY year ORDER BY year DESC','year');
        // pre($arrYears);
        
        // pre($this->dbFormSettings);
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
        // print __FUNCTION__."<hr>";
        // ?end_date=2025-12-31&page=1&start_date=2025-01-01

        // $f = new form();
        // print "<div id='year-selector'>";
        // print "<button type='button'>⟨</button>";
        // print "<div class='wrapper'>";
        // print "<a href='#'>2025</a>";
        // print "<a href='#'>2024</a>";
        // print "</div>";
        // print "<button type='button'>⟩</button>";
        // print "</div>";
        ?>
        <!-- <form id="filter-documents" action="/invoices" accept-charset="UTF-8" method="get"> -->
        <div class="invoice-toolbar">
            <div class="invoice-years-wrap">
                <div class="invoice-years">
                    <?php
                        foreach ($arrYears as $y => $v) {
                            print "<a href='?start_date={$y}-01-01&end_date={$y}-12-31'>$y</a>\n";
                        }
                    ?>
                    <!-- <a href='#'>2025</a>
                    <a href='#'>2024</a> -->
                </div>
            </div>
            <div class="invoice-filters">
                <!-- <form id="filter-documents" action="/invoices" accept-charset="UTF-8" method="get"> -->
                    <input type="date" name="start_date" id="start_date" value="<?=$start_date;?>" max="2100-12-31" data-type="date" class="datepicker searchbar_date">
                    <div class="px-1">–</div>
                    <input type="date" name="end_date" id="end_date" value="<?=$end_date;?>" max="2100-12-31" data-type="date" class="datepicker searchbar_date">
                    <input type="search" name="search" id="search" value="" data-controller="autofocus" placeholder="Sök" class="search searchbar_search" autocomplete="off" data-initialized="1">
                <!-- </form> -->
            </div>

        </div>
        <!-- </form> -->


        <?php
        $datPeriodStart =  $start_date; //"2025-01-01";
        $datPeriodEnd = $end_date; //"2025-12-31";
        // 0 = due
        // 1 = unpaied
        // 2 = paied
        $arrInvoices = $db->o_get_all('invoice', "SELECT * FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID() . " AND (invoicedate>='{$datPeriodStart}' AND invoicedate<='{$datPeriodEnd}')  ORDER BY paied ASC, invoicedate DESC"); // AND paied=0 AND dueDate < CURDATE() ORDER BY dueDate ASC   ,showDebug:true   LIMIT 0,3
        $now = new DateTime();
        $invoiceData = [];
        // pre($arrInvoices);
        foreach ($arrInvoices as $k => $v) {
            $sum = unserialize($v['data'])['total'] ?? NULL;
            // Check if invoices is paied or not
            if ($v['paied'] == 0) {
                // Check if invoice is past due
                if (new DateTime($v['dueDate']) < $now) {
                    // print "Date is past";
                    $invoiceData[0][$k] = $v;
                    $t = 0;
                    if (is_null($sum)) continue;
                    if(!isset($invoiceData[$t]['sum'])) {
                        $invoiceData[$t]['sum'] = $sum;
                    } else {
                        // pre($sum);
                        foreach ($sum as $sk => $sv) {
                            if (!isset($invoiceData[$t]['sum'][$sk])) {
                                $invoiceData[$t]['sum'][$sk] = 0;
                            }
                            (float)$invoiceData[$t]['sum'][$sk]+=(float)$sv;
                        }
                    }
                } else {
                    // print "Date OK";
                    $invoiceData[1][$k] = $v;
                    $t = 1;
                    if (is_null($sum)) continue;
                    if(!isset($invoiceData[$t]['sum'])) {
                        $invoiceData[$t]['sum'] = $sum;
                    } else {
                        // pre($sum);
                        foreach ($sum as $sk => $sv) {
                            if (!isset($invoiceData[$t]['sum'][$sk])) {
                                $invoiceData[$t]['sum'][$sk] = 0;
                            }
                            (float)$invoiceData[$t]['sum'][$sk]+=(float)$sv;
                        }
                    }
                }
            } else {
                // print "Paied";
                    $invoiceData[2][$k] = $v;
                    $t = 2;
                    if (is_null($sum)) continue;
                    if(!isset($invoiceData[$t]['sum'])) {
                        $invoiceData[$t]['sum'] = $sum;
                    } else {
                        // pre($sum);
                        foreach ($sum as $sk => $sv) {
                            if (!isset($invoiceData[$t]['sum'][$sk])) {
                                $invoiceData[$t]['sum'][$sk] = 0;
                            }
                            (float)$invoiceData[$t]['sum'][$sk]+=(float)$sv;
                        }
                    }
            }
        }
        // Present LIST here
        for ($i=0; $i < 3; $i++) { 
            if (isset($invoiceData[$i]) && count( $invoiceData[$i])>0) {
                    $sum = $invoiceData[$i]['sum'];
                    $tax = 0;
                    foreach ($sum as $sk => $sv) {
                        if (str_starts_with($sv,'tax')) {
                            (float)$tax+=(float)$sv;
                        }
                    }
                    unset($invoiceData[$i]['sum']);
                    print "<p>" .  nl2br(sprintf($GLOBALS['LANG']['MODULE']['invoice']['dblist'][$i], fix_number($sum['sum']), fix_number($tax))) . "</p>";
                    $dblist->setData($invoiceData[$i]);
                    $dblist->MakeWithSettings($this->dbFormSettings);
            }
        }
    }
    /**
     * Mark invoice as SENT
     */
    private function sentInvoice()
    {
        $input = new input();
        $db = db::getInstance();
        $id = intval($input->get('id'));
        if ($id > 0) {
            if (salt($id . date("Y-m-d"), 20) == $input->get('auth_token')) {
                if (!$this->doNotSave) {
                    if ($db->o_update('invoice', array('printed' => 1, 'printed_date' => date("Y-m-d")), 'id=' . $id)) {
                        setMsg('s', $GLOBALS['LANG']['MODULE']['invoice']['sent']);
                    } else {
                        setMsg('e', $GLOBALS['LANG']['MODULE']['invoice']['no_sent']);
                    }
                }
            }
        }
    }

    /**
     * Mark invoice as Paied
     */
    private function paiedInvoice()
    {
        $input = new input();
        $arrPost = $input->postAll();
        $db = db::getInstance();
        $id = intval($arrPost['id']);
        if ($id > 0) {
            if (salt($id) == $arrPost['authenticity_token']) {
                if ($arrPost['payment']['paid_at'] !== "") {
                    // setMsg('n','Datumet sätts till ' . $arrPost['payment']['paid_at']);
                    if (!$this->doNotSave) {
                        if ($db->o_update('invoice', array('paied' => 1, 'paied_date' => $arrPost['payment']['paid_at']), 'id=' . $id)) {
                            setMsg('s', $GLOBALS['LANG']['MODULE']['invoice']['paid']);
                        } else {
                            setMsg('e', $GLOBALS['LANG']['MODULE']['invoice']['err_paid']);
                        }
                    }
                } else {
                    setMsg('e', $GLOBALS['LANG']['MODULE']['invoice']['err_paid']);
                }
            }
        }
    }

    private function unpaiedInvoice()
    {
        $input = new input();
        $db = db::getInstance();
        $id = intval($input->get('id'));
        if ($id > 0) {
            if (salt($id, 20) == $input->get('auth')) {
                if (!$this->doNotSave) {
                    if ($db->o_update('invoice', array('paied' => 0, 'paied_date' => ''), 'id=' . $id)) {
                        setMsg('s', $GLOBALS['LANG']['MODULE']['invoice']['no_paid']);
                    } else {
                        setMsg('e', $GLOBALS['LANG']['MODULE']['invoice']['err_paid']);
                    }
                }
            }
        }
    }

    /**
     * Save the invoice
     */
    public static function createWaterInvoices(array $post): void
    {
        $post['watermeter_year'] = (int)date('Y');
        $instance = new self(false);
        invoice_editor::numbered(db::getInstance(), (int)getCompanyID(), function () use ($instance, $post) {
            $instance->saveInvoice($post);
        });
    }

    private function saveInvoice($post = null)
    {

        // NYTT!!! Allt som har med fakturan att göra skall finnas i arrayen $arrInvoice
        $input = new input();
        $arrPost = invoice_editor::submission($post ?? $input->postAll(), (int)getCompanyID());
        $db = db::getInstance();




        // pre($arrPost);
        //        print getCompanyID();
        // Hämta företagsinfo
        /**
         * VIKTIGT!!!
         *
         * Skall det inte vara så att i tabellen company så skall man söka efter den som har companyID = company.id?? JAPP!! FIXAT
         *
         */
        //        print "<pre>";
        //        print_r($_POST);
        //        print "</pre>";







        $arrCompany = $db->o_get_row('company', 'SELECT * FROM [table] WHERE deleted=0 AND id=' . getCompanyID() . ' ORDER BY id DESC');
        if (!$arrCompany) throw new RuntimeException('Företagsuppgifter saknas. Ingen faktura skapades.');

        //        print "<pre>";
        //        print_r($arrCompany);
        //        print "</pre>";



        //Sätter init strängar och nollningar här
        $bolMultiInvoices = false;

        // SQL satsen byggs olika beroende på om en eller flera
        $strWhere = " AND 1=2";
        //        if ($arrPost['db_select_all']) {
        //            $bolMultiInvoices = true;
        //            $strWhere = "";
        //        } elseif (intval($arrPost['db_customer'])>0) {
        //            $intCustomer = intval($arrPost['db_customer']);
        //            $strWhere = " AND id=" . $intCustomer;
        //        }

        if (isset($arrPost['db_select_all_user'])) {
            $bolMultiInvoices = true;
            // Bygg upp alla användare här.
            $strWhere = "";
            foreach ($arrPost['db_select_all_user'] as $all_value) {
                if (is_numeric($all_value)) {
                    $strWhere .= " OR id=" . $all_value . " ";
                }
            }
            if (strlen($strWhere) > 0) {
                $strWhere = " AND (" . substr($strWhere, 3) . ") ";
            }
        } elseif (intval($arrPost['db_customer_id']) > 0) {
            $intCustomer = intval($arrPost['db_customer_id']);
            $strWhere = " AND id=" . $intCustomer;
        }
            //    print $strWhere;

        /**
         * VIKTIGT!!!
         *
         * Skall det inte vara så att i tabellen customer så skall man söka efter den som har companyID = customer.companyid??
         *
         */

        // Hämta kund/er
        $arrAllCustomer = $db->o_get_all('customer', 'SELECT * FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID() . $strWhere);
        // pre($arrAllCustomer);
        if (count($arrAllCustomer) === 0) throw new Exception('Ingen vald kund hittades i det aktuella f?retaget.');
        if (isset($arrPost['db_select_all_user']) && count($arrAllCustomer) !== count($arrPost['db_select_all_user'])) {
            throw new RuntimeException('Alla valda kunder måste finnas i det aktuella företaget.');
        }
        $waterQuantities = [];
        $waterYear = isset($arrPost['watermeter_year']) ? (int)$arrPost['watermeter_year'] : (int)date('Y');
        $hasWater = false;
        foreach ($arrPost['db_invoice'] as $row) {
            if ($row['product_connected'] === 'watermeter' && $row['product_amount'] === 'x') $hasWater = true;
        }
        if (isset($arrPost['watermeter_year']) && !$hasWater) throw new RuntimeException('Behåll vattenraden med automatiskt antal x.');
        if ($hasWater) {
            $customerIds = array_map(function ($customer) { return (int)$customer['id']; }, array_values($arrAllCustomer));
            $estates = $db->o_get_all('estate', 'SELECT id,pid,adress FROM [table] WHERE pid IN (' . implode(',', $customerIds) . ')');
            $readings = $db->o_get_all('watermeter', "SELECT pid,datum,reading FROM [table] WHERE datum>='" . ($waterYear - 1) . "-01-01' AND datum<'" . ($waterYear + 1) . "-01-01'");
            if (!is_array($estates) || !is_array($readings)) throw new RuntimeException('Kunde inte läsa vattenunderlaget.');
            $waterQuantities = invoice_water_usage::quantities($estates, $readings, $customerIds, $waterYear);
            $previous = $db->o_get_all('invoice', 'SELECT customer_id,data FROM [table] WHERE companyid=' . (int)getCompanyID());
            if (!is_array($previous)) throw new RuntimeException('Kunde inte kontrollera tidigare vattenfakturor.');
            foreach ($previous as $issued) {
                $data = unserialize($issued['data'], ['allowed_classes' => false]);
                if ((int)($data['watermeter_year'] ?? 0) === $waterYear && in_array((int)$issued['customer_id'], $customerIds, true)) {
                    throw new RuntimeException('Kund ' . $issued['customer_id'] . ' har redan en vattenfaktura för ' . $waterYear . '. Ingen ny omgång skapades.');
                }
            }
        }
        $arrAllInvoices = [];
        if (count($arrAllCustomer) > 0) {
            foreach ($arrAllCustomer as $key => $arrCustomer) {
                $customer_id = (int)$arrCustomer['id'];
                // pre($arrCustomer);
                // die();
                //                print "<pre>";
                //                print_r($arrCustomer);
                //                print "</pre>";
                // Fix om annan adress för faktura
                if ($arrCustomer['fakt_adress'] != "" && $arrCustomer['fakt_postnr'] != "" && $arrCustomer['fakt_postort'] != "") {
                    $arrCustomer['adress'] = $arrCustomer['fakt_adress'];
                    $arrCustomer['postnr'] = $arrCustomer['fakt_postnr'];
                    $arrCustomer['postort'] = $arrCustomer['fakt_postort'];
                }
                // Fixar referensen
                //                $arrPost['yourRef'] = $arrCustomer['namn'];

                // Hämta senaste fakturanummer
                $intInvoiceID = invoice_editor::nextNumber($db, (int)getCompanyID());
                /*
                 * en array med invoice och invoice total
                 */
                // Reset array and build it up
                $arrInvoice = array();
                if ($hasWater) $arrInvoice['watermeter_year'] = $waterYear;
                $arrInvoice['title'] = $GLOBALS['LANG']['MODULE']['invoice']['title'][0];
                $arrInvoice['company'] = $arrCompany;
                $arrInvoice['customer'] = $arrCustomer;
                $arrInvoice['invoice'] = array(
                    'invoiceno'         => $intInvoiceID,
                    'invoicedate'       => $arrPost['db_invoicedate'],
                    'yourRef'           => $arrCustomer['namn'],
                    'termsOfPayment'    => $arrPost['db_termsOfPayment'],
                    'ourRef'            => $arrCompany['ourRef'],
                    'dueDate'           => $arrPost['db_dueDate'],
                );


                $arrInvoice['total'] = array(
                    'of25' => 0, 'of12' => 0, 'of6' => 0, 'of0' => 0,
                    'tax25' =>  0,
                    'tax12' =>  0,
                    'tax6'  =>  0,
                    'fix'   =>  0,
                    'sum'   =>  0,
                );

                // pre($arrPost['invoice']);
                $dblTotal = 0;
                // Calculating array
                foreach ($arrPost['db_invoice'] as $inv_key => $inv_value) {
                // foreach ($arrPost['invoice'] as $inv_key => $inv_value) {
                    // pre($inv_key . "  " . $inv_value);
                    // pre($inv_value['product_amount']);
                    // if (isSuperUser()) {
                    if ($inv_value['product_amount'] === 'x' && $inv_value['product_connected'] === 'watermeter') {
                        $inv_value['product_amount'] = $waterQuantities[$customer_id];
                    }
                    if (!is_numeric($inv_value['product_amount'])) throw new Exception('Vattenförbrukningen kunde inte beräknas för kunden. Kontrollera mätvärdena innan fakturering.');
                    $arrInvoice['rows'][$inv_key] = $inv_value;
                    $arrInvoice['rows'][$inv_key]['product_total'] =  floatval($arrInvoice['rows'][$inv_key]['product_amount']) * floatval($arrInvoice['rows'][$inv_key]['product_price']);
                    // Beräkna momsen på denna rad
                    if (!isset($arrInvoice['rows'][$inv_key]['product_tax'])) {
                        $arrInvoice['rows'][$inv_key]['product_tax'] = 0;
                    }
                    if ($arrInvoice['rows'][$inv_key]['product_tax'] != 0) {
                        // Beräknar momsen
                        $strTax = $arrInvoice['rows'][$inv_key]['product_tax'];
                        $sumTax = ($arrInvoice['rows'][$inv_key]['product_total'] * $strTax) / 100;
                        // Lägger till i of kategorin
                        $arrInvoice['total']['of' . $strTax] = $arrInvoice['total']['of' . $strTax] + $arrInvoice['rows'][$inv_key]['product_total'];
                        // Räknar och lägger ihop
                        $arrInvoice['rows'][$inv_key]['product_total'] = $arrInvoice['rows'][$inv_key]['product_total'] + $sumTax;
                        $arrInvoice['total']['tax' . $strTax] = $arrInvoice['total']['tax' . $strTax] + $sumTax;
                    }
                    $dblTotal = $dblTotal + floatval($arrInvoice['rows'][$inv_key]['product_total']);
                    // Här ska momsen läggas på i olika kategorier om de fanns

                }
                foreach (getTaxRates() as $key => $value) {
                    if (!isset($arrInvoice['total']['tax' . $key])) {
                        $arrInvoice['total']['tax' . $key] = 0;
                    }
                }

                // Beräkna öresutjämning
                $rounded = round($dblTotal);
                $arrInvoice['total']['fix'] = $rounded - $dblTotal;
                $dblTotal = $dblTotal + $arrInvoice['total']['fix'];

                $arrInvoice['total']['sum'] = $dblTotal;
                // Vad heter filen??
                $strFilename = $arrCompany['orgnr'] . "-" . str_pad($intInvoiceID, 10, "0", STR_PAD_LEFT);
                $arr2DB = array(
                    'invoiceno'         => $intInvoiceID,
                    'customer_id'       => $arrCustomer['id'],
                    'customer'          => serialize($arrCustomer),
                    'invoicedate'       => $arrPost['db_invoicedate'],
                    'yourRef'           => $arrCustomer['namn'],
                    'termsOfPayment'    => $arrPost['db_termsOfPayment'],
                    'ourRef'            => $arrCompany['ourRef'],
                    'dueDate'           => $arrPost['db_dueDate'],
                    'data'              => serialize($arrInvoice),
                    'companyid'         => (int)getCompanyID(),
                    'sum'               => $dblTotal,
                    'filename'          => $strFilename . '.pdf',
                    //'printed_date'      => , // ruta för att räkna dem som utskrivna!
                );

                // Spara fakturan i DB
                if (!$this->doNotSave) {
                    if (!$db->o_insert('invoice', $arr2DB)) throw new Exception('Fakturan kunde inte sparas. Ingen PDF har skapats.');
                    setMsg('s', sprintf($GLOBALS['LANG']['MODULE']['invoice']['invoice_made'], $intInvoiceID, $GLOBALS['CONFIG']['www-root'], $strFilename));
                }

                if ($this->doNotSave) {
                    setMsg('s', sprintf($GLOBALS['LANG']['MODULE']['invoice']['invoice_made'], $intInvoiceID, $GLOBALS['CONFIG']['www-root'], $strFilename));
                }

                // Skapa fakturan som PDF
                include(__DIR__ . '/invoice_template.php');
                // if(isSuperUser()) print $html;
            }
            // Fler än en faktura gjordes
            if (count($arrAllInvoices) > 1) {
                //                print "<pre>";
                //                print_r($arrAllInvoices);
                //                print "</pre>";
                require_once ROOTPATH . 'system/libs/pdf.cls.php';
                $mpdf = new pdf();
                $mpdf->SetDisplayMode('fullpage');
                $x = 0;
                $header = "
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
                $footer = "</body></html>";
                $mpdf->WriteHTML($header);
                foreach ($arrAllInvoices as $xkey => $xhtml) {
                    if ($x > 0) {
                        $mpdf->AddPage('c', 'A4', '', '', 32, 25, 27, 25, 16, 13);
                    }
                    $mpdf->WriteHTML($xhtml);
                    $x++;
                }
                $mpdf->WriteHTML($footer);
                $strFilename = getCompanyFileName() . "-samlingsfakturor-" . date("Y_m_d_H_i_s");
                $filename = ROOTPATH . "pdf/" . $strFilename . ".pdf";
                if (is_file($filename)) @unlink($filename);
                $mpdf->Output($filename, "F"); //I
                setMsg('s', sprintf($GLOBALS['LANG']['MODULE']['invoice']['invoice_made_multi'], $GLOBALS['CONFIG']['www-root'], $strFilename));
            }
        }
    }

    /**
     * Make the invoice
     */
    private function makeInvoice()
    {
        invoice_editor::render(db::getInstance(), (int)getCompanyID());
    }

    private function loadSettings()
    {


        $this->dbFormSettings = $GLOBALS['TABLE']['invoice'];
        return;


        $db = db::getInstance();
        //        print 'SELECT * FROM [table] WHERE deleted=0 AND id=' . getCompanyID() .' ORDER BY id DESC';
        $arrCompany = $db->o_get_row('company', 'SELECT * FROM [table] WHERE deleted=0 AND id=' . getCompanyID() . ' ORDER BY id DESC');
        //        print "<pre>";
        //        print_r($arrCompany);
        //        print "</pre>";



        $this->dbFormSettings = array(
            'table' => 'invoice',
            'list'  => array(
                'order' => 'invoiceno,yourRef,sum,dueDate,paied,printed,filename',
                'width' => '',
                'edit'  => false,
            ),
            'list_paied'  => array(
                'order' => 'invoiceno,yourRef,sum,paied_date,paied,printed,filename',
                'width' => '',
                'edit'  => false,
            ),
            'form'    =>  array(
                //                    'palette' =>  'customer,select_all,invoicedate,yourRef,termsOfPayment,ourRef,dueDate,companyid,auth_token',//,save_callback , invoiceno
                'palette' =>  'customer,customer_id,invoicedate,yourRef,termsOfPayment,ourRef,dueDate,companyid,auth_token', //,save_callback , invoiceno
            ),
            'fields'  =>  array(
                // Nedan för adress och sånt
                /*                    'customer'    =>  array(
                        'type'    => 'select',
                        'name'    => 'customer',
                        'field'    =>  'customer',
                        'foreignKey'    =>  'customer.namn',
                        'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['customer'],
//                        'multiple'  => true,
                    ),*/
                'customer'    =>  array(
                    'type'    => 'varchar',
                    //                        'name'    => 'customer',
                    'field'    =>  'customer',
                    //                        'foreignKey'    =>  'customer.namn',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['customer'],
                    //                        'multiple'  => true,
                    //'class_list'   => 'w100pct',
                    'eval' => array('div_class' => 'w100pct'),
                ),
                'customer_id'    =>  array(
                    'type'    => 'hidden',
                ),

                //                    'id'  =>  array(
                //                        'type'    => 'int',
                //                        'field'    =>  'id',
                //
                //                        'list_callback' =>  'modInvoiceButtonBar'
                //                    ),
                'invoiceno'  =>  array(
                    'type'    => 'int',
                    'field'    =>  'invoiceno',
                    'value'   => 1,
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['invoiceno'],
                ),
                'select_all'  =>  array(
                    'type'    => 'checkbox',
                    'field'    =>  'select_all',
                    //                        'value'   => 1,
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['select_all'],
                ),

                'invoicedate'  =>  array(
                    'type'    => 'date',
                    'value'   => getTodayDate(),
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['invoicedate'],
                ),
                'yourRef'  =>  array(
                    'type'    => 'varchar',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['yourRef'],
                ),
                'termsOfPayment'  =>  array(
                    'type'    => 'varchar',
                    'value'   => 30,
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['termsOfPayment'],
                ),
                'ourRef'  =>  array(
                    'type'    => 'varchar',
                    'value'   => $arrCompany['ourRef'],
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['ourRef'],
                ),
                'dueDate'  =>  array(
                    'type'    => 'varchar',
                    'value' =>  getDateFromToday(30),
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['dueDate'],
                ),
                'companyid'  =>  array(
                    'type'    => 'hidden',
                    'value' => getCompanyID(),
                    //                        'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['dueDate'],
                ),
                'auth_token'  =>  array(
                    'type'    => 'hidden',
                    'value' =>  salt(getCompanyID() . date("Y-m-d"), 30),
                    //                        'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['dueDate'],
                ),



                // Nedanför fält i fakturans köp del

                'product_name'    =>  array(
                    'type'    => 'varchar',
                    'field'   =>  'invoice[0][product_name]',
                    'class'   => 'product_name',
                    'size'   => 20,
                    'placeholder'   =>  $GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_name'],
                    'dataautocomplete' =>  '/test.php',
                    'dataid'    => '0',
                ),
                'product_text'  =>  array(
                    'type'    => 'varchar',
                    'field'   =>  'invoice[0][product_text]',
                    'class'   => 'product_text',
                    'size'   => 28,
                    'placeholder'   =>  $GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_text'],
                ),
                'product_amount'    =>  array(
                    'type'    => 'varchar',
                    'field'   =>  'invoice[0][product_amount]',
                    //                      'caption'   => $GLOBALS['LANG']['MODULE']['tablemaker']['length'],
                    'class'   => 'product_amount',
                    'size'   => 5,
                    //                        'value' =>  1,
                ),
                'product_unit'    =>  array(
                    'type'    => 'varchar',
                    'field'   =>  'invoice[0][product_unit]',
                    //                      'caption'   => $GLOBALS['LANG']['MODULE']['tablemaker']['default'],
                    'class'   => 'product_unit',
                    'size'   => 8,
                    'placeholder'   => $GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_unit'],
                ),
                'product_price'   => array(
                    'type' => 'varchar',
                    'field'   =>  'invoice[0][product_price]',
                    //                    'field'    =>  'default_callback',
                    //                    'caption' => $GLOBALS['LANG']['MODULE']['tablemaker']['default_callback'],
                    //                    'options_callback'=>'avalibleValues',
                    'class'   => 'product_price',
                    'size'   => 8,
                    //                        'value' => '9.0',
                ),
                'product_tax'   => array(
                    'type' => 'select',
                    'field'   =>  'invoice[0][product_tax]',
                    //                    'field'    =>  'default_callback',
                    //                    'caption' => $GLOBALS['LANG']['MODULE']['tablemaker']['default_callback'],
                    //                    'options_callback'=>'avalibleValues',
                    'options'   => getTaxRates(),
                    'class'   => 'product_tax',
                    //                        'size'   => 5,
                    //                        'value' => '9.0',
                ),


                //paied, paied_date, printed_date, cancelled, customer_id, sum

                'data'    =>  array(
                    'type'    => 'blob',
                ),
                'paied'    =>  array(
                    'type'    => 'tinyint',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['paied'],
                    'list_callback' =>  ['dcaInvoice','paied'] //'modInvoicePaid',
                ),
                'paied_date'    =>  array(
                    'type'    => 'date',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['paied_date'],
                ),
                'cancelled'    =>  array(
                    'type'    => 'tinyint',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['cancelled'],
                ),
                'sum'    =>  array(
                    'type'    => 'text',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['sum'],
                    'list_callback' =>  'modInvoiceFormatCurrency',
                    'class_list'    =>  'right',
                ),
                'printed'    =>  array(
                    'type'    => 'tinyint',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['printed'],
                    'list_callback' =>  'modInvoicePrinted',
                ),
                'printed_date'    =>  array(
                    'type'    => 'date',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['printed_date'],
                ),
                'filename'    =>  array(
                    'type'    => 'text',
                    'caption'   => $GLOBALS['LANG']['MODULE']['invoice']['filename'],
                    'list_callback' =>  'modInvoiceButtonBar',
                ),

            ),
        );
    }

    private function remindInvoice() {}

    /**
     * Make a CREDIT of Invoice
     */
    private function creditInvoice()
    {
        $input = new input();
        // Kollar id och auth
        $id = intval($input->get('id'));
        if ($id <= 0) {
            return;
        }
        if ($input->get('auth_token') != salt(intval($id) . date('Y-m-d'), 20)) {
            return;
        }

        $db = db::getInstance();
        // Hämta senaste fakturanummer
        $intInvoiceID = invoice_editor::nextNumber($db, (int)getCompanyID());

        // Hämtar fakturan som ska krediteras
        $arrXInvoice = $db->o_get_row('invoice', 'SELECT * FROM [table] WHERE deleted=0 AND companyid=' . (int)getCompanyID() . ' AND id=' . $id . ' ORDER BY id DESC');
        if (!is_array($arrXInvoice)) {
            return;
        }
        // Hämta data
        $arrInvoice = unserialize($arrXInvoice['data']);

        // Sätt title
        $arrInvoice['title'] = $GLOBALS['LANG']['MODULE']['invoice']['title'][1];
        // Sätt nytt fakturanr
        $arrInvoice['invoice']['invoiceno'] = $intInvoiceID;

        // Beräkna om raderna
        foreach ($arrInvoice['rows'] as $key => $value) {
            if ($value['product_amount'] >= 0) {
                $arrInvoice['rows'][$key]['product_amount'] = -abs($value['product_amount']);
            } elseif ($value['product_amount'] <= 0) {
                $arrInvoice['rows'][$key]['product_amount'] = abs($value['product_amount']);
            }
            if ($value['product_total'] >= 0) {
                $arrInvoice['rows'][$key]['product_total'] = -abs($value['product_total']);
            } elseif ($value['product_total'] <= 0) {
                $arrInvoice['rows'][$key]['product_total'] = abs($value['product_total']);
            }
        }
        // Beräkna om summorna
        foreach ($arrInvoice['total'] as $key => $value) {
            if ($value >= 0) {
                $arrInvoice['total'][$key] = -abs($value);
            } elseif ($value <= 0) {
                $arrInvoice['total'][$key] = abs($value);
            }
        }

        // Fixa filnamnet
        $strFilename = $arrInvoice['company']['orgnr'] . "-" . str_pad($intInvoiceID, 10, "0", STR_PAD_LEFT);
        // Märk den som betald
        if (!$this->doNotSave) {
            $db->o_update('invoice', array('paied' => 1, 'paied_date' => date("Y-m-d")), 'id=' . $id);
        }

        // Bygger nya DB fixet
        unset($arrXInvoice['id']);
        $arr2DB = array(
            'invoiceno'         =>  $arrInvoice['invoice']['invoiceno'],
            'customer_id'       =>  $arrInvoice['customer']['id'],
            'customer'          =>  serialize($arrInvoice['customer']),
            'invoicedate'       =>  date("Y-m-d"),
            'yourRef'           =>  $arrInvoice['customer']['namn'],
            'termsOfPayment'    =>  $arrInvoice['invoice']['termsOfPayment'],
            'ourRef'            =>  $arrInvoice['invoice']['ourRef'],
            'dueDate'           =>  getDateFromToday(intval($arrInvoice['invoice']['termsOfPayment'])),
            'data'              =>  serialize($arrInvoice),
            'companyid'         =>  (int)getCompanyID(),
            'sum'               =>  $arrInvoice['total']['sum'],
            'filename'          =>  $strFilename . '.pdf',
            'paied'             =>  1,
            'paied_date'        =>  date("Y-m-d"),
        );
        // Spara fakturan i DB
        if (!$this->doNotSave) {
            if ($db->o_insert('invoice', $arr2DB)) {
                setMsg('s', sprintf($GLOBALS['LANG']['MODULE']['invoice']['invoice_credit'], $intInvoiceID, $GLOBALS['CONFIG']['www-root'], $strFilename));
            }
        }
        // Skapa fakturan som PDF
        include(__DIR__ . '/invoice_template.php');
    }

    private function sendInvoice()
    {
        // print __FUNCTION__;
        $input = new input();
        // Kollar id och auth
        $id = intval($input->get('id'));
        if ($id <= 0) {
            return;
        }
        if ($input->get('auth_token') != salt(intval($id) . date('Y-m-d'), 20)) {
            return;
        }
        // Check email for valid

        // Send invoice, on success set user sent to 1 and sent date to today.



        setMsg('e', "Denna funktion är inte implementerad ännu!");
        // print "SUCCESS!";
    }

    private function sendAllInvoices()
    {
        setMsg('e', "Denna funktion är inte implementerad ännu!");
    }
}

function modInvoiceButtonBar($strField, $dca)
{
    return invoice_view::actions($dca);
}

function modInvoiceFormatCurrency($strField, $dca)
{
    return sprintf($GLOBALS['LANG']['MODULE']['invoice']['currency_list'], fix_number($strField));
}

function modInvoicePaid($strField, $dca)
{
    return invoice_view::paid($dca);
}

function modInvoicePrinted($strField, $dca)
{
    return invoice_view::printed($dca);
}

function modInvoiceGetCustomers()
{
    $db = db::getInstance();
    //        print 'SELECT * FROM [table] WHERE deleted=0 AND id=' . getCompanyID() .' ORDER BY id DESC';
    $arrCompany = $db->o_get_all('customer', 'SELECT id,namn FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID() . ' ORDER BY namn ASC');
    return (array) $arrCompany;
}
