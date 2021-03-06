<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2010 Franz Holzinger (franz@ttproducts.de)
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
 * hook functions for TYPO3 FE extensions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_hooks_be implements \TYPO3\CMS\Core\SingletonInterface {

	public function displayCategoryTree ($PA, $fobj) {
		$result = false;

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('mbi_products_categories')) {
			$treeObj = false;

			if (class_exists('JambageCom\\MbiProductsCategories\\View\\TreeSelector')) {
				$treeObj = GeneralUtility::makeInstance('JambageCom\\MbiProductsCategories\\View\\TreeSelector');
			} else if (class_exists('tx_mbiproductscategories_treeview')) {
				$treeObj = GeneralUtility::makeInstance('tx_mbiproductscategories_treeview');
			}

			if (is_object($treeObj)) {
				$result = $treeObj->displayCategoryTree($PA, $fobj);
			}
		}

		return $result;
	}


	public function displayOrderHtml ($PA, $fobj) {
		$result = 'ERROR';

		$table = $PA['table'];
		$field = $PA['field'];
		$row   = $PA['row'];

			// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];
		$orderData = unserialize($row['orderData']);

		if (
			is_array($orderData) &&
			isset($orderData['html_output']) &&
			isset($config['parameters']) &&
			is_array($config['parameters']) &&
			isset($config['parameters']['format']) &&
			$config['parameters']['format'] == 'html'
		) {
			$result = $orderData['html_output'];
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_be.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_be.php']);
}


