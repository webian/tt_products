<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the tables of static_info_tables
 *
 * @author Franz Holzinger <franz@ttproducts.de>
 * @maintainer Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_static_info implements \TYPO3\CMS\Core\SingletonInterface {

	static private $staticInfo = false;

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	static public function init () {
		$result = false;

		if (
			!is_object(self::$staticInfo) &&
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')
		) {
			$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables');

			if (is_array($eInfo)) {
				$sitVersion = $eInfo['version'];
                $class = 'SJBR\\StaticInfoTables\\PiBaseApi';

                // Initialise static info library
                self::$staticInfo = GeneralUtility::makeInstance('' . $class);
                if (
                    !method_exists(self::$staticInfo, 'needsInit') ||
                    self::$staticInfo->needsInit()
                ) {
                    self::$staticInfo->init();
                }
	
				if (is_object(self::$staticInfo)) {
					$result = true;
				} else {
					self::$staticInfo = false;
				}
			} else {
				// TODO: inform the user about wrong version of static_info_tables
			}
		}

		return $result;
	} // init

	static public function getStaticInfo () {
		return self::$staticInfo;
	}
}

