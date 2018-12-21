<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$table = 'tt_products_graduated_price';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
}



$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);

if (
    !empty($orderBySortingTablesArray) &&
    in_array($table, $orderBySortingTablesArray)
) {
    $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
}

