<?php
$GLOBALS['TABLE']['customer'] = array(
	'table' => 'customer',
	'list'  => array(
		'order' => 'namn,adress,postnr,postort,email',
		'width' => '',
		'edit'  => true
	),
	'allowAjax' => 1,
	'search'    => array(
		'searchfields'  =>  'namn,adress,postort,email',
		'searchcaption' =>  'Sök kund',
		'type'          =>  'text',
		//'order'         =>  'datum DESC',
		//'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array(
		'palette'   =>  'id,namn,adress,postnr,postort,fastighet,telefon,kommentar,ordning,deleted,companyid,visible,fakt_adress,fakt_postnr,fakt_postort,email'
	),
	'fields' => array(
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['customer']['id']
		),
		'namn' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['namn']
		),
		'adress' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['adress']
		),
		'postnr' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['postnr']
		),
		'postort' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['postort']
		),
		'fastighet' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['fastighet']
		),
		'telefon' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['telefon']
		),
		'kommentar' => array(
			'type'     => 'text',
			'caption'  => $GLOBALS['LANG']['customer']['kommentar']
		),
		'ordning' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['customer']['ordning']
		),
		'deleted' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['customer']['deleted']
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['customer']['companyid'],
			'value'    => getCompanyID(),
		),
		'visible' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['customer']['visible']
		),
		'fakt_adress' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['fakt_adress']
		),
		'fakt_postnr' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['fakt_postnr']
		),
		'fakt_postort' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['fakt_postort']
		),
		'email' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['customer']['email']
		)
	)
);
