<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$table = 'tt_products_articles';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
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

