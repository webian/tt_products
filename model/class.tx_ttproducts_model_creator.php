<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * class for data collection
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_model_creator implements \TYPO3\CMS\Core\SingletonInterface {

	public function init (&$conf, &$config, $cObj)  {

		tx_ttproducts_static_info::init();

		$bUseStaticTaxes = false;
		if (t3lib_extMgm::isLoaded('static_info_tables')) {
			$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables');

			if (is_array($eInfo))	{
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.0', '>='))	{
					if ($conf['useStaticTaxes'] && t3lib_extMgm::isLoaded('static_info_tables_taxes')) {
						$eInfo2 = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables_taxes');

						if (is_array($eInfo2)) {
							$sittVersion = $eInfo2['version'];
							if (version_compare($sittVersion, '0.3.0', '>=')) {
								$bUseStaticTaxes = true;
							}
						}
					}
				}
			}
		}

		if (isset($conf['UIDstore']))	{
			$tmpArray = GeneralUtility::trimExplode(',',$conf['UIDstore']);
			$UIDstore = $tmpArray['0'];
		}

		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$taxObj->preInit(
			$cObj,
			$bUseStaticTaxes,
			$UIDstore
		);

			// price
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$priceObj->preInit(
			$cObj,
			$conf
		);

			// paymentshipping
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		$paymentshippingObj->init(
			$cObj,
			$priceObj
		);

		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket'); // TODO: initialization
	}

	public function destruct () {
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_model_creator.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_model_creator.php']);
}



