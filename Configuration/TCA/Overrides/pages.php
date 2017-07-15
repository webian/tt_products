<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$imageFile = PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif';

if (
    version_compare(TYPO3_version, '7.5.0', '>')
) {

    // add folder icon
    $pageType = 'ttproducts'; // a maximum of 10 characters
    $iconReference = 'apps-pagetree-folder-contains-' . $pageType;

    $addToModuleSelection = true;
    foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
        if ($item['1'] == $pageType) {
            $addToModuleSelection = false;
            break;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-' . $pageType] = $iconReference;
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:pageModule.plugin',
            1 => $pageType,
            2 => $iconReference
        );
    }

    $callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';
    call_user_func(
        $callingClassName . '::registerPageTSConfigFile',
        $pageType,
        'Configuration/TSconfig/Page/folder_tables.txt',
        'EXT:' . TT_PRODUCTS_EXT . ' :: Restrict pages to tt_products records'
    );
} else {
    // add folder icon
    $pageType = 'ttpproduct';

    $callingClassName = '\\TYPO3\\CMS\\Backend\\Sprite\\SpriteManager';
    if (
        class_exists($callingClassName) &&
        method_exists($callingClassName, 'addTcaTypeIcon')
    ) {
        call_user_func(
            $callingClassName . '::addTcaTypeIcon',
            'pages',
            'contains-' . $pageType,
            $imageFile
        );
    } else {
        t3lib_SpriteManager::addTcaTypeIcon(
            'pages',
            'contains-' . $pageType,
            $imageFile
        );
    }

    $addToModuleSelection = TRUE;
    foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
        if ($item['1'] == $pageType) {
            $addToModuleSelection = FALSE;
            break;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:pageModule.plugin',
            1 => $pageType,
            2 => $imageFile
        );
    }
}

