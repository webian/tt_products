<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$table = 'tt_products';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
}

$bSelectTaxMode = FALSE;

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime', 'usebydate', 'sellstarttime', 'sellendtime');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
}

if (
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(STATIC_INFO_TABLES_TAXES_EXT)
) {
	$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003(STATIC_INFO_TABLES_TAXES_EXT);

	if (is_array($eInfo)) {
		$sittVersion = $eInfo['version'];
		if (version_compare($sittVersion, '0.3.0', '>=')) {
			$bSelectTaxMode = TRUE;
		}
	}
}



if ($bSelectTaxMode) {
	$whereTaxCategory = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('static_tax_categories');

	$temporaryColumns = array (
		'tax_id' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.0', '0'),
					array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.1', '1'),
					array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.2', '2'),
					array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.3', '3'),
					array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.4', '4'),
					array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.5', '5'),
				),
			)
		),
	);


	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
		$table,
		$temporaryColumns
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
		$table,
		'tax_id',
		'',
		'replace:tax_dummy'
	);

	$GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] = str_replace(',tax,', ',tax,tax_id,', $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']);

}

switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode']) {
	case '1':
		$GLOBALS['TCA'][$table]['columns']['article_uid'] = array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.article_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_articles',
				'MM' => 'tt_products_products_mm_articles',
				'foreign_table' => 'tt_products_articles',
				'foreign_table_where' => ' ORDER BY tt_products_articles.title',
				'size' => 10,
				'selectedListStyle' => 'width:450px',
				'minitems' => 0,
				'maxitems' => 1000,
			)
		);
		break;
	case '2':
		// leave the settings of article_uid
		break;
    case '0':
    default:
        unset($GLOBALS['TCA'][$table]['columns']['article_uid']);
        $GLOBALS['TCA'][$table]['types']['0'] = str_replace(',article_uid,', ',', $GLOBALS['TCA'][$table]['types']['0']);
        break;
}

$addressTable = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addressTable'];

if (!$addressTable) {
    $addressTable = 'fe_users';
}

$GLOBALS['TCA'][$table]['columns']['address'] = array (
    'exclude' => 1,
    'label' => DIV2007_LANGUAGE_LGL . 'address',
    'config' => array (
        'type' => 'group',
        'internal_type' => 'db',
        'allowed' => $addressTable,
        'size' => 1,
        'minitems' => 0,
        'maxitems' => 1,
    )
);

$newFields = 'address';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    $table,
    $newFields,
    '',
    'before:price'
);

$excludeArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'];

if (
    isset($excludeArray) &&
    is_array($excludeArray) &&
    isset($excludeArray[$table])
) {
    \JambageCom\Div2007\Utility\TcaUtility::removeField(
        $GLOBALS['TCA'][$table],
        $excludeArray[$table]
    );
}

