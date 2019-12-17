<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Milosz Klosowicz (typo3@miklobit.com)
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
 * currency functions
 *
 * @author  Milosz Klosowicz <typo3@miklobit.com>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_currency_view implements \TYPO3\CMS\Core\SingletonInterface {

	public $pibase; // reference to object of pibase
	public $conf;
	public $subpartMarkerObj; // marker functions
	public $urlObj;

	public function init($pibase) {
		$this->pibase = $pibase;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$this->conf = &$cnf->conf;

		$this->subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($pibase->cObj);
		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
	}


	/**
	 * currency selector
	 */
	function printView()  {
		$currList = $this->exchangeRate->initCurrencies($this->BaseCurrency);
		$jScript =  '	var currlink = new Array(); '.chr(10);
		$index = 0;
		foreach( $currList as $key => $value)	{
			//$url = $this->getLinkUrl('','',array('C' => 'C='.$key));
			$url = $this->pibase->pi_getPageLink($GLOBALS['TSFE']->id,'',$this->urlObj->getLinkParams('',array('C' => 'C='.$key),true));
			$jScript .= '	currlink['.$index.'] = "'.$url.'"; '.chr(10) ;
			$index ++ ;
		}

		$content = tx_div2007_core::getSubpart($this->templateCode, $this->subpartmarkerObj->spMarker('###CURRENCY_SELECTOR###'));
		$content = $this->pibase->cObj->substituteMarker( $content, '###CURRENCY_FORM_NAME###', 'tt_products_currsel_form' );
		$onChange = 'if (!document.tt_products_currsel_form.C.options[document.tt_products_currsel_form.C.selectedIndex].value) return; top.location.replace(currlink[document.tt_products_currsel_form.C.selectedIndex] );';
		$selector = $this->exchangeRate->buildCurrSelector($this->BaseCurrency,'C','',$this->currency, $onChange);
		$content = $this->pibase->cObj->substituteMarker( $content, '###SELECTOR###', $selector );

		// javascript to submit correct get parameters for each currency
		$GLOBALS['TSFE']->additionalHeaderData['tx_ttproducts'] = '<script type="text/javascript">'.chr(10).$jScript.'</script>';
		return $content ;
	}

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_currency_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_currency_view.php']);
}



