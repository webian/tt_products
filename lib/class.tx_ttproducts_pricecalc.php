<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * Part of the tt_products (Shopping System) extension.
 *
 * basket price calculation functions without any object
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *  
 */



class tx_ttproducts_pricecalc {
	var $pibase; // reference to object of pibase
	var $basket;

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init(&$pibase, &$basket, &$tt_products)	{
		global $TSFE;

 		$this->pibase = &$pibase;
 		$this->basket = &$basket; 
 		$this->tt_products = &$tt_products;
	}

	/**
	 * Include calculation script which should be programmed to manipulate internal data.
	 */
	function includeCalcScript($calcScript, &$confScript, &$basketView)	{
		include($calcScript);
	} // includeCalcScript

	/**
	 * Include handle script
	 */
	function includeHandleScript($handleScript, &$confScript, &$basketView)	{
		$content = '';
		include($handleScript);
		return $content;
	} // includeHandleScript

	function GetCalculatedData(&$conf) { // delete countTotal if not neede any more
		global $TSFE;

		if (!$this->basket->itemArray) {
			return;
		}
		$getDiscount = 0;

		$gr_list = explode (',' , $TSFE->gr_list);

		if ($conf['getDiscountPrice']) {
			$getDiscount = 1;
		} else {
			while (list(,$val) = each ($gr_list)) {
				if ((intval($val) > 0) && ($getDiscount == 0)) {
					$getDiscount = 1 - strcmp($TSFE->fe_user->groupData->title, $conf['discountGroupName '] );

					if (strlen($TSFE->fe_user->groupData['title']) == 0)	// repair result of strcmp
						$getDiscount = 0;
				}
			}
		}

		$priceTotal = array();
		$priceReduction = array();

		$additive = 0;
		// Check if a special group price can be used
		if (($getDiscount == 1) && ($conf['discountprice.'] != NULL))
		{
			$countTotal = 0;
			$countedItems = array();

			ksort($conf['discountprice.']);
			reset($conf['discountprice.']);

			$type = '';
			$field = '';
			foreach ($conf['discountprice.'] as $k1=>$priceCalcTemp) {
				foreach ($priceCalcTemp as $k2=>$v2) {
					if (!is_array($priceCalcTemp)) {
						switch ($k2) {
							case 'type':
								$type = $v2;
								break;
							case 'field':
								$field = $v2;
								break;
							case 'additive':
								$additive = $v2;
								break;
						}
						continue;
					}
				}
				$dumCount = 0;
				$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
				$pricefor1Index = 100*$pricefor1;

				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->basket->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k2=>$actItem) {
					// count all items which will apply to the discount price
							$count2 = $actItem['count'];
							if (($count2 > 0) && ($actItem['rec']['price'] == $pricefor1)) {
								$countedItems [$pricefor1Index][] = array ('pid' => $pid, 'itemnumber' => $itemnumber);
								$dumCount += $count2;
							}
						}
					}
				}
				$countTotal += $dumCount;

				if ($additive == 0) {
					krsort($priceCalcTemp['prod.']);
					reset($priceCalcTemp['prod.']);
					foreach ($priceCalcTemp['prod.'] as $k2=>$price2) {
						if ($dumCount >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
							if (intval($k2) > 1) {
								// store the discount price in all calculated items from before
//								foreach ($countedItems as $pricefor1Index=>$v3) {
								foreach ($countedItems[$pricefor1Index] as $k4 => $v4) {
									foreach ($this->basket->itemArray[$v4['pid']] [$v4['itemnumber']] as $k5=>$actItem) {
									 	$this->basket->itemArray[$v4['pid']] [$v4['itemnumber']][$k5] ['calcprice'] = $price2;
									}
								}
//								}
								$priceReduction[$pricefor1Index] = 1; // remember the reduction in order not to calculate another price with $priceCalc
							}
							else {
								$priceReduction[$pricefor1Index] = 0;
							}
							break; // finish
						}
					}
				}
			}

			if ($additive == 1) {

				reset($conf['discountprice.']);

				foreach ($conf['discountprice.'] as $k1=>$priceCalcTemp) {
					if (!is_array($priceCalcTemp)) {
						continue;
					}
					$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
					if ($countedItems [100*$pricefor1] == NULL) {
						continue;
					}

					krsort($priceCalcTemp['prod.']);
					reset($priceCalcTemp['prod.']);
					while (list ($k2, $price2) = each ($priceCalcTemp['prod.'])) {
						if ($countTotal >= intval($k2)) { // search the price from the total count
							if (intval($k2) > 1) {
								// store the discount price in all calculated items from before
								foreach ($countedItems[$pricefor1Index] as $k3=>$v3) {
									foreach ($this->basket->itemArray[$v3['pid']] [$v3['itemnumber']] as $k1=>$actItem) { 
									 	$this->basket->itemArray[$v3['pid']] [$v3['itemnumber']][$k1] ['calcprice'] = $price2;
									}
								}
								$priceReduction[$pricefor1Index] = 1; // remember the reduction in order not to calculate another price with $priceCalc later
							}
							else  {	// $priceTotal [$k1] contains the product count
								$priceReduction[$pricefor1Index] = 0;
							}
							break; // finish
						}
					}
				}
			}
			else
			{	// nothing
			}
		}

		if ($conf['pricecalc.']) {
			$countTotal = 0;

			ksort($conf['pricecalc.']);
			reset($conf['pricecalc.']);

			foreach ($conf['pricecalc.'] as $k1=>$priceCalcTemp) {
				if (!is_array($priceCalcTemp)) {
					continue;
				}
				$countedItems = array();

				$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
				$pricefor1Index = 100*$pricefor1;

				// has the price already been calculated before ?
				if ($priceReduction[$pricefor1Index] == 1) {
					continue;
				}
				$dumCount = 0;

				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->basket->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k2=>$actItem) {
							// count all items which will apply to the discount price
							$count2 = $actItem['count'];
							if (($count2 > 0) && ($actItem['rec']['price'] == $pricefor1)) {
								$countedItems [$pricefor1Index][] = array ('pid' => $pid, 'itemnumber' => $itemnumber);
								$dumCount += $count2;
							}
						}
					}
				}
				
					// nothing found?
				if ($dumCount == 0) {
					continue;
				}

				$countTotal += $dumCount;

				$priceTotalTemp = 0;
				$countTemp = $dumCount;
				krsort($priceCalcTemp['prod.']);
				reset($priceCalcTemp['prod.']);
				foreach ($priceCalcTemp['prod.'] as $k2=>$price2) {
					if (intval($k2) > 0) {
						while ($countTemp >= intval($k2)) {
							$countTemp -= intval($k2);
							$priceTotalTemp += doubleval($price2);
						}
					}
				}

				$priceProduct = ($dumCount > 0 ? ($priceTotalTemp / $dumCount) : 0);
				foreach ($countedItems[$pricefor1Index] as $k3=>$v3) {
					foreach ($this->basket->itemArray[$v3['pid']] [$v3['itemnumber']] as $k4=>$actItem) {
						$this->basket->itemArray[$v3['pid']] [$v3['itemnumber']] [$k4] ['calcprice'] = $priceProduct;
					}
				}
			}
		}

	} // GetCalculatedData
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricecalc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricecalc.php']);
}

?>