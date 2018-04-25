<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$table = 'sys_products_orders';

$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
    !empty($orderBySortingTablesArray) &&
    in_array($table, $orderBySortingTablesArray)
) {
    $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
}


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

