<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Franz Holzinger (franz@ttproducts.de)
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
 * main class for eID AJAX function to change the values of records for the
 * variant select box
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_db implements t3lib_Singleton {
	protected $extKey = TT_PRODUCTS_EXT;	// The extension key.
	protected $conf;			// configuration from template
	protected $config;
	protected $ajax;
	protected $LLkey;
	protected $cObj;
	public $LOCAL_LANG = Array();		// Local Language content
	public $LOCAL_LANG_charset = Array();	// Local Language content charset for individual labels (overriding)
	public $LOCAL_LANG_loaded = 0;		// Flag that tells if the locallang file has been fetch (or tried to be fetched) already.


	public function init (&$conf, &$config, &$ajax, &$pObj)	{
		$this->conf = &$conf;

		if (isset($ajax) && is_object($ajax))	{
			$this->ajax = &$ajax;

			$ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXT.'_fetchRow',$this,'fetchRow'));
			$ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXT.'_commands',$this,'commands'));

			$ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXT . '_showArticle', $this, 'showArticle'));

		}

		if (
			is_object($pObj) &&
			isset($pObj->cObj) &&
			is_object($pObj->cObj)
		) {
			$this->cObj = $pObj->cObj;
		} else {
		    $this->cObj = t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
		    $this->cObj->start(array());
// TODO: $cObj->start($contentRow,'tt_content');
		}

		$controlCreatorObj = t3lib_div::makeInstance('tx_ttproducts_control_creator');
		$controlCreatorObj->init($conf, $config, $pObj, $this->cObj);

		$modelCreatorObj = t3lib_div::makeInstance('tx_ttproducts_model_creator');
		$modelCreatorObj->init($conf, $config, $this->cObj);
	}


	public function main ()	{
	}


	public function printContent ()	{
	}


	public function &fetchRow ($data) {
		global $TYPO3_DB, $TSFE;

		$rc = '';
		$view = '';
		$rowArray = array();
		$variantArray = array();
		$theCode = 'ALL';
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');

			// price
		$priceObj = t3lib_div::makeInstance('tx_ttproducts_field_price');
		$priceObj->init(
			$this->cObj,
			$this->conf
		);
		$priceViewObj = t3lib_div::makeInstance('tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$langObj,
			$this->cObj,
			$priceObj
		);
		$discount = $TSFE->fe_user->user['tt_products_discount'];

        // We put our incomming data to the regular piVars
		$itemTable = $tablesObj->get('tt_products', FALSE);

		if (is_array($data))	{
			$useArticles = $itemTable->variant->getUseArticles();

			foreach ($data as $k => $dataRow)	{

				if ($k == 'view')	{
					$view = $dataRow;
					$theCode = strtoupper($view);
				} else if(is_array($dataRow))	{
					$table = $k;
					$uid = $dataRow['uid'];

					if ($uid)	{
						$row = $itemTable->get($uid);

						if ($row)	{

							if ($useArticles == 3)	{
								$itemTable->fillVariantsFromArticles($row);
								$articleRows = $itemTable->getArticleRows(intval($row['uid']));
							}
							$rowArray[$table] = $row;
							foreach ($row as $field => $v)	{
								if ($field != 'uid' && isset($dataRow[$field]))	{
									$variantArray[] = $field;
									$variantValues = t3lib_div::trimExplode(';', $v);

									$theValue = $variantValues[$dataRow[$field]];
									$rowArray[$table][$field] = $theValue;
								}
							}
							$tmpRow = $rowArray[$table];
							$bKeepNotEmpty = FALSE;

							if ($useArticles == 1)	{
								$rowArticle = $itemTable->getArticleRow($rowArray[$table], $theCode, FALSE);
							} else if ($useArticles == 3) {

								$rowArticle = $itemTable->getMatchingArticleRows($tmpRow, $articleRows);
								$bKeepNotEmpty = $this->conf['keepProductData'];
							}

							if ($rowArticle)	{

								$itemTable->mergeAttributeFields($tmpRow, $rowArticle, $bKeepNotEmpty, TRUE);
								$tmpRow['uid'] = $uid;
							}

							$totalDiscountField = $itemTable->getTotalDiscountField();
							$itemTable->getTotalDiscount($tmpRow);
							$priceTaxArray = array();

							$priceTaxArray = $priceObj->getPriceTaxArray(
								$this->conf['discountPriceMode'], $basketObj->basketExtra, 'price', tx_ttproducts_control_basket::getRoundFormat(), tx_ttproducts_control_basket::getRoundFormat('discount'), $tmpRow, $totalDiscountField, $priceTaxArray);

							$csConvObj = $TSFE->csConvObj;
							$field = 'price';
							foreach ($priceTaxArray as $priceKey => $priceValue) {
								$displayTax = $priceViewObj->convertKey($priceKey, $field);
								$displaySuffixId = str_replace('_', '', strtolower($displayTax));
								$tmpRow[$displaySuffixId] = $priceValue;
							}

							if ($rowArticle)	{
								if (!$rowArticle['image'])	{
									$rowArticle['image'] = $rowArray[$table]['image'];
									$tmpRow['image'] = $rowArticle['image'];
								}

								$articleConf = $cnf->getTableConf('tt_products_articles', $theCode);
								if (
									isset($articleConf['fieldIndex.']) && is_array($articleConf['fieldIndex.']) &&
									isset($articleConf['fieldIndex.']['image.']) && is_array($articleConf['fieldIndex.']['image.'])
								)	{
									$prodImageArray = t3lib_div::trimExplode(',',$rowArray[$table]['image']);
									$artImageArray = t3lib_div::trimExplode(',',$rowArticle['image']);
									$tmpDestArray = $prodImageArray;
									foreach($articleConf['fieldIndex.']['image.'] as $kImage => $vImage)	{
										$tmpDestArray[$vImage-1] = $artImageArray[$kImage-1];
									}
									$tmpRow['image'] = implode (',', $tmpDestArray);
								}
								// $rowArray[$table] = $tmpRow;
							}

 							$itemTable->getTableObj()->substituteMarkerArray($tmpRow);
							$rowArray[$table] = $tmpRow;
						} // if ($row ...)
					}
				}
			}
			$this->ajax->setConf($data['conf']);
		}

		$rc = $this->generateResponse($view, $rowArray, $variantArray);
		return $rc;
	}


	protected function &generateResponse ($view, &$rowArray, &$variantArray)	{
		global $TSFE;

		$csConvObj = $TSFE->csConvObj;

		$theCode = strtoupper($view);
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
		$imageObj = t3lib_div::makeInstance('tx_ttproducts_field_image');
		$imageViewObj = t3lib_div::makeInstance('tx_ttproducts_field_image_view');
		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');

		$imageObj->init($this->cObj);
		$imageViewObj->init($langObj, $imageObj);

		$priceObj = t3lib_div::makeInstance('tx_ttproducts_field_price');
			// price
		$priceViewObj = t3lib_div::makeInstance('tx_ttproducts_field_price_view');

		$priceFieldArray = $priceObj->getPriceFieldArray();
		$tableObjArray = array();
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');

		// Instantiate the tx_xajax_response object
		$objResponse = new tx_taxajax_response($this->ajax->taxajax->getCharEncoding(), TRUE);

		foreach ($rowArray as $functablename => $row)	{ // tt-products-list-1-size
			if (!is_object($tableObjArray[$functablename]))	{
				$suffix = '-from-tt-products-articles';
			} else {
				$suffix = '';
			}

			$itemTableView = $tablesObj->get($functablename, TRUE);
			$itemTable = $itemTableView->getModelObj();

			$jsTableNamesId = str_replace('_','-',$functablename).$suffix;
			$uid = $row['uid'];
			foreach ($row as $field => $v)	{

				if ($field == 'additional')	{
					continue;
				}
				if (($field == 'title') || ($field == 'subtitle') || ($field == 'note') || ($field == 'note2'))	{
					if (
						version_compare(TYPO3_version, '6.0.0', '<') &&
						$GLOBALS['TSFE']->renderCharset != '' &&
						$v != ''
					) {
						$v = $csConvObj->conv($v, $GLOBALS['TSFE']->renderCharset, $this->ajax->taxajax->getCharEncoding());
					}

					if (($field == 'note') || ($field == 'note2'))	{
						$noteObj = t3lib_div::makeInstance('tx_ttproducts_field_note_view');
						$classAndPath = $itemTable->getFieldClassAndPath($field);

						if ($classAndPath['class'])	{
							$tmpArray = array();
							$fieldViewObj = $itemTableView->getObj($classAndPath);
							$modifiedValue =
								$fieldViewObj->getRowMarkerArray	(
									$functablename,
									$field,
									$row,
									$tmp,
									$tmpArray,
									$tmpArray,
									$theCode,
									'',
									$basketObj->basketExtra,
									$tmp=FALSE,
									TRUE,
									'',
									'',
									'',
									''
								);

							if (
								version_compare(TYPO3_version, '6.0.0', '<') &&
								$GLOBALS['TSFE']->renderCharset != '' &&
								$modifiedValue != ''
							) {
								$v = $csConvObj->conv(
									$modifiedValue,
									$GLOBALS['TSFE']->renderCharset,
									$this->ajax->taxajax->getCharEncoding()
								);
							}
						}
					}
				}
//
				if (!in_array($field, $variantArray))	{
					$tagId = $jsTableNamesId.'-'.$view.'-'.$uid.'-'.$field;
					switch ($field)	{
						case 'image': // $this->cObj

							$imageRenderObj = 'image';
							if ($theCode == 'LIST' || $theCode == 'SEARCH') {
								$imageRenderObj = 'listImage';
							}
							$imageArray = $imageObj->getImageArray($row, 'image');
							$dirname = $imageObj->getDirname($row);
							$theImgDAM = array();
							$markerArray = array();
							$linkWrap = '';

							$mediaNum = $imageViewObj->getMediaNum (
								'tt_products_articles',
								'image',
								$theCode
							);
							$imgCodeArray = $imageViewObj->getCodeMarkerArray(
								'tt_products_articles',
								'ARTICLE_IMAGE',
								$theCode,
								$row,
								$imageArray,
								$dirname,
								$mediaNum,
								$imageRenderObj,
								$linkWrap,
								$markerArray,
								$theImgDAM,
								$specialConf = array()
							);

							$v = $imgCodeArray;

							break;

						case 'inStock':
							$basketIntoPrefix = tx_ttproducts_model_control::getBasketIntoIdPrefix();
							if ($v > 0)	{
								$objResponse->addClear($basketIntoPrefix . '-' . $uid,'disabled');
							} else {
								$objResponse->addAssign($basketIntoPrefix . '-' . $uid,'disabled', 'disabled');
							}
							$objResponse->addAssign('in-stock-id-'.$uid,'innerHTML',tx_div2007_alpha5::getLL_fh003($langObj, ($v > 0 ? 'in_stock' : 'not_in_stock')));

							break;

						default:
							// nothing
							break;
					}
					if (in_array($field, $priceFieldArray))	{
						$v = $priceViewObj->priceFormat($v);

						if (
							version_compare(TYPO3_version, '6.0.0', '<') &&
							$GLOBALS['TSFE']->renderCharset != '' &&
							$v != ''
						) {
							$v = $csConvObj->conv($v, $GLOBALS['TSFE']->renderCharset, $this->ajax->taxajax->getCharEncoding());
						}
					}

					if (is_array($v))	{
						reset($v);
						$vFirst = current($v);
						$objResponse->addAssign($tagId,'innerHTML', $vFirst);
						$c = 0;
						foreach ($v as $k => $v2)	{
							$c++;
							$tagId2 = $tagId.'-'.$c;
							$objResponse->addAssign($tagId2,'innerHTML', $v2);
						}
					} else {
						$objResponse->addAssign($tagId,'innerHTML', $v);
					}
				}
			}
		}

		$rc = &$objResponse->getXML();
	    //return the XML response generated by the tx_taxajax_response object

		return $rc;
	}

	public function &commands ($cmd,$param1='',$param2='',$param3=''){
		$objResponse = new tx_taxajax_response($this->ajax->taxajax->getCharEncoding());

		switch ($cmd) {
			default:
				$hookVar = 'ajaxCommands';
				if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])) {
					foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
						$hookObj= t3lib_div::makeInstance($classRef);
						if (method_exists($hookObj, 'init')) {
							$hookObj->init($this);
						}
						if (method_exists($hookObj, 'commands')) {
							$tmpArray = $hookObj->commands($cmd,$param1,$param2,$param3, $objResponse);
						}
					}
				}
			break;
		}

		return $objResponse->getXML();
	}


	public function showArticle ($data) {

		if (
			isset($data['tt_content']) &&
			is_array($data['tt_content']) &&
			isset($data['tt_content']['uid'])
		) {
			$contentRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tt_content', 'uid = ' . intval($data['tt_content']['uid']));
			$this->cObj->start($contentRow, 'tt_content');
		}

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$result = '';
		$pibaseObj = t3lib_div::makeInstance('tx_ttproducts_pi1_base');
		$mainObj = t3lib_div::makeInstance('tx_ttproducts_main');

		$piVars = tx_ttproducts_model_control::getPiVars();
		$pibaseObj->piVars = $piVars;

		if (
			isset($data) &&
			is_array($data) &&
			isset($data[$pibaseObj->prefixId]) &&
			is_array($data[$pibaseObj->prefixId])
		) {
			foreach($data[$pibaseObj->prefixId] as $k => $v) {
				tx_ttproducts_model_control::setAndVar($k, $v);
			}
		}

		$this->ajax->conf = $data['conf'];
		$objResponse = new tx_taxajax_response($this->ajax->taxajax->getCharEncoding());
		$pibaseObj->cObj = $this->cObj;

		$content = '';
		$bDoProcessing =
			$mainObj->init(
				$content,
				$cnf->getConf(),
				$cnf->getConfig(),
				'tx_ttproducts_pi1_base',
				$errorCode,
				TRUE
			);

		$code = 'LIST';
		$mainObj->codeArray = array($code);
		$tagId = 'tt-products-' . strtolower($code);

		if ($bDoProcessing || count($errorCode)) {
			$content = $mainObj->run('tx_ttproducts_pi1_base', $errorCode, $content, TRUE);
		}

		$objResponse->addAssign($tagId, 'innerHTML', $content);
		$result = $objResponse->getXML();
	    //return the XML response generated by the tx_taxajax_response object
		return $result;
	}


		// XAJAX functions cannot be in classes
	public function showList ($data) {

		$tagId = '';
		$result = '';
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$mainObj = t3lib_div::makeInstance('tx_ttproducts_main');

		$pibaseObj = t3lib_div::makeInstance('tx_ttproducts_pi1_base');
        // We put our incomming data to the regular piVars

		$piVars = tx_ttproducts_model_control::getPiVars();
		if (isset($piVars) && is_array($piVars)) {
			if (
				isset($data) &&
				is_array($data) &&
				isset($data[$pibaseObj->prefixId]) &&
				is_array($data[$pibaseObj->prefixId])
			) {
				foreach($data[$pibaseObj->prefixId] as $k => $v) {
					if (isset($piVars[$k])) {
						$piVars[$k] .= ',' . $v;
					} else {
						$piVars[$k] = $v;
					}
				}
			}
		}

        // We put our incomming data to the regular piVars
		$pibaseObj->piVars = $piVars;

		$pibaseObj->cObj = $this->cObj;

	    // Instantiate the tx_xajax_response object
	    $objResponse = new tx_xajax_response();

		if (count($this->codeArray)) {
			foreach ($this->codeArray as $k => $code) {
				if ($code != 'LISTARTICLES') {
					unset($this->codeArray[$k]);
				} else {
					$tagId = 'tx-ttproducts-pi1-' . strtolower($code);
				}
			}
		}

		$bDoProcessing =
			$mainObj->init(
				$content,
				$cnf->getConf(),
				$cnf->getConfig(),
				'tx_ttproducts_pi1_base',
				$errorCode,
				TRUE
			);

		if ($tagId != '') {
			if ($bDoProcessing || count($errorCode)) {
				$content = $mainObj->run('tx_ttproducts_pi1_base', $errorCode, $content, TRUE);

				$objResponse->addAssign($tagId, 'innerHTML', $content);

				//return the XML response generated by the tx_xajax_response object
				$result = $objResponse->getXML();
			}
		}

	    return $result;
	}


	public function destruct () {
		$controlCreatorObj = t3lib_div::makeInstance('tx_ttproducts_control_creator');
		$controlCreatorObj->destruct();

		$modelCreatorObj = t3lib_div::makeInstance('tx_ttproducts_model_creator');
		$modelCreatorObj->destruct();

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$tablesObj->destruct();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/eid/class.tx_ttproducts_db.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/eid/class.tx_ttproducts_db.php']);
}

?>
