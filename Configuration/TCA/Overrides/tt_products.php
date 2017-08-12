<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$table = 'tt_products';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime', 'usebydate', 'sellstarttime', 'sellendtime');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
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


