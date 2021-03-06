<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
// products to graduated price relation table, tt_products_mm_graduated_price
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_mm_graduated_price',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
		'hideTable' => true,
	),
	'interface' => array (
		'showRecordFieldList' => 'product_uid,graduated_price_uid'
	),
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'hidden',
			'config' => array (
				'type' => 'check',
				'default' => 0
			)
		),
		'product_uid' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_mm_graduated_price.product_uid',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'graduated_price_uid' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_mm_graduated_price.graduated_price_uid',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products_graduated_price',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'productsort' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
		'graduatedsort' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden,--palette--;;1, product_uid, graduated_price_uid')
	)
);

return $result;

