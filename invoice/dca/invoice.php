<?php
require_once dirname(__DIR__) . "/view.php";
$GLOBALS['TABLE']['invoice']=array (
	'table' => 'invoice',
	'list'  => array(
		'order' => 'invoiceno,yourRef,sum,dueDate,paied,printed,filename',
		'width' => '',
		'edit' => false
	),
	'allowAjax' => 0,
	'search'    => array (
		'searchfields'  =>  'id',
		'searchcaption' =>  $GLOBALS['LANG']['MODULE']['invoice']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array (
		// 'palette'   =>  'id,pid,ordning,companyid,deleted,invoiceno,customer,invoicedate,yourRef,termsOfPayment,ourRef,dueDate,data,paied,paied_date,printed,printed_date,cancelled,customer_id,sum,filename'
		// 'palette'   =>  'id,pid,ordning,companyid,deleted,invoiceno,customer,invoicedate,yourRef,termsOfPayment,ourRef,dueDate,data,paied,paied_date,printed,printed_date,cancelled,customer_id,sum,filename'
		'palette'   =>  'id,pid,ordning,companyid,deleted,invoiceno,customer,invoicedate,yourRef,termsOfPayment,ourRef,dueDate,customer_id'
	),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['id']
		),
		'pid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['pid']
		),
		'ordning' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['ordning']
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid']
		),
		'deleted' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['deleted']
		),
		'visible' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['visible']
		),
		'invoiceno' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['invoiceno']
		),
		'customer' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['customer']
		),
		'invoicedate' => array(
			'type'    => 'date',
			'value'   => getTodayDate(),
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['invoicedate']
		),
		'yourRef' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['yourRef']
		),
		'termsOfPayment' => array(
			'type'     => 'varchar',
			'value'   => 30,
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['termsOfPayment']
		),
		'ourRef' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['ourRef']
		),
		'dueDate' => array(
			'type'     => 'date',
			'value' =>  getDateFromToday(30),
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['dueDate']
		),
		'data' => array(
			'type'     => 'blob',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['data']
		),
		'paied' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['paied'],
			'list_callback' => ['dcaInvoice','modInvoicePaid']
		),
		'paied_date' => array(
			'type'     => 'date',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['paied_date']
		),
		'printed' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['printed'],
			'list_callback' =>  ['dcaInvoice','modInvoicePrinted'],
		),
		'printed_date' => array(
			'type'     => 'date',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['printed_date']
		),
		'cancelled' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['cancelled']
		),
		'customer_id' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['customer_id']
		),
		'sum' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['sum'],
			'list_callback' =>  ['dcaInvoice','modInvoiceFormatCurrency'],
			'class_list'    =>  'right',
		),
		'filename' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['MODULE']['invoice']['filename'],
			'list_callback' =>  ['dcaInvoice','modInvoiceButtonBar'],
		) ,
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
				'product_connected'    =>  array(
                    'type'    => 'hidden',
                    'field'   =>  'invoice[0][product_connected]',
                    'class'   => 'product_connected',
                    // 'size'   => 20,
                    // 'placeholder'   =>  $GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_connected'],
                    // 'dataautocomplete' =>  '/test.php',
                    // 'dataid'    => '0',
                ),
				'product_id'    =>  array(
                    'type'    => 'hidden',
                    'field'   =>  'invoice[0][product_id]',
                    'class'   => 'product_id',
                    // 'size'   => 20,
                    // 'placeholder'   =>  $GLOBALS['LANG']['MODULE']['invoice']['placeholder_product_id'],
                    // 'dataautocomplete' =>  '/test.php',
                    // 'dataid'    => '0',
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
	)
);
// addJS('system/modules/invoice/html/invoice.js');
// addCSS('system/modules/invoice/html/invoice.css');
class dcaInvoice {
	function paied($dca) {
		return $dca['paied'] ? "<span class='material-symbols-outlined' style='color:green;'>check_box</span>" : "<span class='material-symbols-outlined'>check_box_outline_blank</span>";
	}

	function modInvoiceButtonBar($dca)
	{
		return invoice_view::actions($dca);
	}

	function modInvoiceFormatCurrency($dca)
	{
		// return "xxx";
		return sprintf($GLOBALS['LANG']['MODULE']['invoice']['currency_list'], fix_number($dca['sum']));
	}

	function modInvoicePaid($dca)
	{
		return invoice_view::paid($dca);
	}

	function modInvoicePrinted($dca)
	{
		return invoice_view::printed($dca);
	}

	// function modInvoiceGetCustomers()
	// {
	// 	$db = db::getInstance();
	// 	//        print 'SELECT * FROM [table] WHERE deleted=0 AND id=' . getCompanyID() .' ORDER BY id DESC';
	// 	$arrCompany = $db->o_get_all('customer', 'SELECT id,namn FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID() . ' ORDER BY namn ASC');
	// 	return (array) $arrCompany;
	// }

}
