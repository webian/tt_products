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
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/Configuration/TypoScript/PluginSetup/Main/constants.txt
 * @see TSref
 *
 *
 */



class tx_ttproducts_pi_search_base extends tslib_pibase implements t3lib_Singleton {
	public $prefixId = TT_PRODUCTS_EXT;
	public $scriptRelPath = 'pi_search_base/class.tx_ttproducts_pi_search_base.php';	// Path to this script relative to the extension dir.
	public $extKey = TT_PRODUCTS_EXT;	// The extension key.
	public $pi_checkCHash = true;		// activate cHash
	public $bRunAjax = false;		// overrride this


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	public function main ($content,$conf)	{
		tx_ttproducts_model_control::setPrefixId($this->prefixId);
		$this->pi_setPiVarDefaults();
		$confMain = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT.'.'];
		$this->conf = array_merge($confMain, $conf);
		$config = array();
		$mainObj = t3lib_div::makeInstance('tx_ttproducts_control_search');	// fetch and store it as persistent object
		$errorCode = array();
		$bDoProcessing = $mainObj->init($content, $this->conf, $config, get_class($this), $errorCode);

		if ($bDoProcessing || count($errorCode))	{
			$content = $mainObj->run(get_class($this),$errorCode,$content);
		}
		return $content;
	}


	public function set ($bRunAjax)	{
		$this->bRunAjax = $bRunAjax;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi_search/class.tx_ttproducts_pi_search_base.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi_search/class.tx_ttproducts_pi_search_base.php']);
}


