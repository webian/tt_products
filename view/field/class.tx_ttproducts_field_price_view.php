<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * price view functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_field_price_view implements tx_ttproducts_field_view_int, t3lib_Singleton {
	public $langObj;
	public $cObj;
	public $conf;			// original configuration
	public $modelObj;
	protected static $convertArray = array(
		'price' => array(
			'tax' => 'PRICE_TAX',
			'taxperc' => 'TAX',
			'0tax' => 'OLD_PRICE_TAX',
			'0notax' => 'OLD_PRICE_NO_TAX',
			'calc' => 'calcprice',
			'notax' => 'PRICE_NO_TAX',
			'onlytax' => 'PRICE_ONLY_TAX',
			'skontotax' => 'PRICE_TAX_DISCOUNT',
			'skontotaxperc' => 'PRICE_TAX_DISCOUNT_PERCENT',
			'unotax' => 'UNIT_PRICE_NO_TAX',
			'utax' => 'UNIT_PRICE_TAX',
			'wnotax' => 'WEIGHT_UNIT_PRICE_NO_TAX',
			'wtax' => 'WEIGHT_UNIT_PRICE_TAX',
		),
		'price2' => array(
			'2tax' => 'PRICE2_TAX',
			'2notax' => 'PRICE2_NO_TAX',
			'2onlytax' => 'PRICE2_ONLY_TAX',
			'2skontotax' => 'PRICE2_TAX_DISCOUNT',
			'2skontotaxperc' => 'PRICE2_TAX_DISCOUNT_PERCENT',
		),
		'directcost' => array(
			'dctax' => 'DIRECTCOST_TAX',
			'dcnotax' => 'DIRECTCOST_NO_TAX',
		)
	);


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($langObj, &$cObj, &$modelObj)	{
		$this->langObj = $langObj;
		$this->cObj = $cObj;
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$this->conf = $cnf->conf;
		$this->modelObj = $modelObj;
		$this->bHasBeenInitialised = true;
	} // init


	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public function getModelObj ()	{
		return $this->modelObj;
	}


	/**
	 * Generate a graphical price tag or print the price as text
	 */
	public function printPrice ($priceText,$taxInclExcl='')	{
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;

		if (($conf['usePriceTag']) && (isset($conf['priceTagObj.'])))	{
			$ptconf = $conf['priceTagObj.'];
			$markContentArray = array();
			$markContentArray['###PRICE###'] = $priceText;
			$markContentArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha5::getLL_fh003($this->langObj, $taxInclExcl) : '');

			$this->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			$rc = $this->cObj->cObjGetSingle($conf['priceTagObj'], $ptconf);
		} else {
			$rc = $priceText;
		}
		return $rc;
	}


	/**
	 * Formatting a price
	 */
	public function priceFormat ($double) {
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;
		$double = round($double, 10);

		if ($conf['noZeroDecimalPoint'] && round($double, 2) == intval($double)) {
			$rc = number_format($double, 0, $conf['priceDecPoint'], $conf['priceThousandPoint']);
		} else {
			$rc = number_format($double, intval($conf['priceDec']), $conf['priceDecPoint'], $conf['priceThousandPoint']);
		}

		if ($rc == '-0,00') {
			$rc = '0,00';
		}

		return $rc;
	} // priceFormat

	/**
	 * Formatting a percentage
	 */
	public function percentageFormat ($double) {
		$result = FALSE;
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;
		$double = round($double, 10);

		$percentageDecPoint = isset($conf['percentageDecPoint']) ? $conf['percentageDecPoint'] : $conf['priceDecPoint'];
		$percentageThousandPoint = isset($conf['percentageThousandPoint']) ? $conf['percentageThousandPoint'] : $conf['priceThousandPoint'];
		$percentDec = isset($conf['percentDec']) ? $conf['percentDec'] : $conf['priceDec'];
		$percentNoZeroDecimalPoint = isset($conf['percentNoZeroDecimalPoint']) ? $conf['percentNoZeroDecimalPoint'] : $conf['noZeroDecimalPoint'];

		if ($percentNoZeroDecimalPoint && round($double, 2) == intval($double)) {
			$result = number_format($double, 0, $percentDecPoint, $percentThousandPoint);
		} else {
			$result = number_format($double, intval($percentDec), $percentDecPoint, $percentThousandPoint);
		}
		return $result;
	} // percentageFormat


	static public function convertKey ($priceType, $fieldname)	{
		$rc = FALSE;
		if (isset(self::$convertArray[$fieldname]) && is_array(self::$convertArray[$fieldname]))	{
			$rc = self::$convertArray[$fieldname][$priceType];
		}
		return $rc;
	}


	public function getModelMarkerArray ($functablename, $basketExtra, $field, $row, &$markerArray, $priceMarkerPrefix, $id)	{

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;
		$config = $cnf->config;
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$itemTableView = $tablesObj->get($functablename, TRUE);
		$itemTable = $itemTableView->getModelObj();
		$modelObj = $this->getModelObj();
		$totalDiscountField = $itemTable->getTotalDiscountField();

		if ($priceMarkerPrefix != '')	{
			$priceMarkerPrefix.='_';
		}
		$priceMarkerArray = array();

		$priceNo = intval($config['priceNoReseller']);
		$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
		$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage($basketExtra);
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');

		$priceTaxArray = array();
		$priceTaxArray = $modelObj->getPriceTaxArray($conf['discountPriceMode'], $basketExtra, $field, tx_ttproducts_control_basket::getRoundFormat(), tx_ttproducts_control_basket::getRoundFormat('discount'), $row, $totalDiscountField, $priceTaxArray);

		foreach ($priceTaxArray as $priceKey => $priceValue)	{
			$displayTax = $this->convertKey($priceKey,$field);
			if ($displayTax != '')	{
				$displayKey = $priceMarkerPrefix . $displayTax;
				$priceFormatted = '';

				if(strpos($priceKey, 'perc') !== FALSE) {
					$priceFormatted = $this->percentageFormat($priceValue);
				} else {
					$priceFormatted = $this->priceFormat($priceValue);
				}

				$priceMarkerArray['###' . $displayKey . '###'] = $this->printPrice($priceFormatted, $taxInclExcl);

				$displaySuffixId = str_replace('_', '', strtolower($displayTax));
				$priceMarkerArray['###'.$displayKey.'_ID###'] = $id . '-' . $displaySuffixId;
			}
		}

		$priceMarkerArray['###CUR_SYM###'] = ' ' . ($conf['currencySymbol'] ? ($charset ? htmlentities($this->conf['currencySymbol'], ENT_QUOTES, $charset) : $conf['currencySymbol']) : '');

		$priceMarkerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha5::getLL_fh003($this->langObj, $taxInclExcl) : '');

		if (is_array($markerArray))	{
			$markerArray = array_merge($markerArray, $priceMarkerArray);
		} else {
			$markerArray = $priceMarkerArray;
		}
	} // getModelMarkerArray


	public function getRowMarkerArray ($functablename, $fieldname, $row, $markerKey, &$markerArray, $tagArray, $theCode, $id, $basketExtra, &$bSkip, $bHtml=true, $charset='', $prefix='', $suffix='', $imageRenderObj='')	{

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$itemTableView = $tablesObj->get($functablename, TRUE);
		$itemTable = $itemTableView->getModelObj();
		$modelObj = $this->getModelObj();
		$marker = strtoupper($fieldname);
		$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
		$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage($basketExtra);
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && ($taxFromShipping == 0) ? 'tax_zero' : 'tax_included');
// tt-products-single-1-pricetax

		$totalDiscountField = $itemTable->getTotalDiscountField();
		$priceTaxArray = array();

		$priceTaxArray = $modelObj->getPriceTaxArray($conf['discountPriceMode'],$basketExtra, $fieldname, tx_ttproducts_control_basket::getRoundFormat(), tx_ttproducts_control_basket::getRoundFormat('discount'), $row, $totalDiscountField, $priceTaxArray);

		$priceMarkerPrefix = $itemTableView->getMarker() . '_';

		foreach ($priceTaxArray as $priceType => $priceValue)	{
			$displayTax = self::convertKey($priceType, $fieldname);
			$taxMarker = $priceMarkerPrefix . strtoupper($displayTax);
			$priceFormatted = '';

			if(strpos($priceKey, 'perc') !== FALSE) {
				$priceFormatted = $this->percentageFormat($priceValue);
			} else {
				$priceFormatted = $this->priceFormat($priceValue);
			}

			$markerArray['###' . $taxMarker . '###'] = $this->printPrice($priceFormatted, $taxInclExcl);
			$displaySuffixId = str_replace('_', '', strtolower($displayTax));
			$displaySuffixId = str_replace($fieldname, '', $displaySuffixId);
			$markerArray['###'.$taxMarker.'_ID###'] = $id . $displaySuffixId;
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_price_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_price_view.php']);
}


?>
