<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$table = 'sys_products_orders';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'date_of_birth', 'date_of_payment', 'date_of_delivery');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
}

