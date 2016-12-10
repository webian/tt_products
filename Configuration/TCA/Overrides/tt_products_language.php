<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$table = 'tt_products_language';
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

if (version_compare(TYPO3_version, '7.6.0', '>=')) {

	unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']);
	unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']);
}
