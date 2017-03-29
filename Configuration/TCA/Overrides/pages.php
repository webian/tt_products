<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$imageFile = PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif';

if (
    version_compare(TYPO3_version, '7.5.0', '>')
) {
    $iconRegistry =
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'tcarecords-pages-contains-tt_products',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        array('source' => $imageFile)
    );

    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tt_products'] =
    'tcarecords-pages-contains-tt_products';
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
            continue;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:pageModule.plugin',
            1 => $pageType,
            2 => 'EXT:' . TT_PRODUCTS_EXT . '/res/icons/table/tt_products.gif'
        );
    }
}

