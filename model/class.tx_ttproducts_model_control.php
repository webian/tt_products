<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
* Part of the tt_products (Shop System) extension.
*
* functions for the page
*
* @author	Franz Holzinger <franz@ttproducts.de>
* @maintainer	Franz Holzinger <franz@ttproducts.de>
* @package TYPO3
* @subpackage tt_products
*
*/

use TYPO3\CMS\Core\Utility\ArrayUtility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_model_control {
    static public $controlVar = 'ctrl';

    static public $paramsTableArray = array(
        'a' => 'address',
        'article' => 'tt_products_articles',
        'cat' => 'tt_products_cat',
        'content' => 'tt_content',
        'dam' => 'tx_dam',
        'damcat' => 'tx_dam_cat',
        'dl' => 'tt_products_downloads',
        'fal' => 'sys_file_reference',
        'fg' => 'fegroup',
        'o' => 'sys_products_orders',
        'oa' => 'orderaddress',
        'pid' => 'pages',
        'product' => 'tt_products',
    );

    static private $prefixId = 'tt_products';
    static private $piVars = array();
    static private $piVarsDefault = array();


    static public function setPiVars ($value) {
        self::$piVars = $value;
    }

    /* if a default value is set then it will merge the current self::$piVars array onto these default values. */
    static public function getPiVars () {
        $result = self::$piVars;
        return $result;
    }

    static public function getPiVar ($functablename) {
        $paramsTableArray = self::getParamsTableArray();
        $result = array_search($functablename, $paramsTableArray);
        return $result;
    }

    static public function getParamsTableArray () {
        return self::$paramsTableArray;
    }
}


