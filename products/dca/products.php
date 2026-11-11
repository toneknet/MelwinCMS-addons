<?php

/* 
 * The MIT License
 *
 * Copyright 2015 Martin Tonek <martin@tonek.se>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
/*
print "<pre>";
print_r(serialize(array('table'=>'company','form'=>array('palette'=>'id,namn,dbprefix,companyid'),'allowAjax'=>0,'list'=>array('where'=>''))));

print_r(unserialize('a:4:{s:5:"table";s:4:"user";s:4:"form";a:1:{s:7:"palette";s:53:"id,user,namn,shortdriver,email,groups,grupp,companyid";}s:9:"allowAjax";i:0;s:4:"list";a:1:{s:5:"where";s:0:"";}}'));
print "</pre>";
*/

$GLOBALS['LANG']['products']['id'] = array ('','');
$GLOBALS['LANG']['products']['label'] = array ('Produktnamn','Här anger du produktens namn');
$GLOBALS['LANG']['products']['unit'] = array ('Enhet för produkt','Här anger du i vilken enhet produkten används. Ex. st/hg/kg');
$GLOBALS['LANG']['products']['price'] = array ('Pris för produkt','Här anger du priset för produkten per enhet');
$GLOBALS['LANG']['products']['tax'] = array ('Momssats','');
$GLOBALS['LANG']['products']['connected'] = array('Ansluten post','Här kan du ange om det finns koppling till annan tabell. t.ex. watermeter');


 $GLOBALS['TABLE']['products']=array (
	'table' => 'products',
	'list'  => array(
		'order' => 'label,unit,price,tax',
		'width' => '100%',
		'edit' => true,
        'where' => 'deleted=0'
	),
	'allowAjax' => 0,
	'form'      =>  array (
		'palette'   =>  'id,label,unit,price,tax,connected,companyid'
	),
	'fields' => array (
        'connected' => ['type' => 'varchar', 'caption' => $GLOBALS['LANG']['products']['connected']],
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['products']['id']
		),
		'label' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['products']['label']
		),
		'unit' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['products']['unit'],
            'class_list'    =>  'right',
		),
		'price' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['products']['price'],
            'list_callback' => 'DBProductsFormatCurrency',
            'class_list'    =>  'right',
		),         
		'tax' => array(
			'type'     => 'select',
            'options'   => getTaxRates(),
			'caption'  => $GLOBALS['LANG']['products']['tax'],
            'list_callback' => 'DBProductsAddPercent',
            'class_list'    =>  'right',
		),     
		'companyid' => array(
			'type'     => 'varchar',
            'value'     => getCompanyID(),
			'caption'  => $GLOBALS['LANG']['products']['price']
		) 
     )
);

function DBProductsAddPercent($strField,$dca) {
    return $strField . " %";
}

function DBProductsFormatCurrency($strField, $dca) {
    return fix_number($strField). " kr";
}