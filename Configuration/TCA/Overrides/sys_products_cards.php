<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$table = 'sys_products_cards';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    unset($GLOBALS['TCA'][$table]['columns']['endtime']['config']['renderType']);
    $GLOBALS['TCA'][$table]['columns']['endtime']['config']['max'] = '20';
}

