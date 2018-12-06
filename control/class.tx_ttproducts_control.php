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
 * class with functions to control all activities
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_control implements t3lib_Singleton {
	public $pibase; // reference to object of pibase
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $basket; 	// the basket object
	public $templateCode='';		// In init(), set to the content of the templateFile. Used by default in getView()
	public $activityArray;		// activities for the CODEs
	public $funcTablename;
	public $error_code = array();
	public $subpartmarkerObj; // subpart marker functions
	public $urlObj; // url functions
	public $urlArray; // overridden url destinations
	public $useArticles;


	public function init ($pibaseClass, $conf, $config, $funcTablename, &$templateCode, $useArticles, &$error_code)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$this->pibaseClass = $pibaseClass;
		$this->pibase = t3lib_div::makeInstance('' . $pibaseClass);
		$this->cObj = $this->pibase->cObj;
		$this->conf = $conf;
		$this->config = $config;
		$this->templateCode = &$templateCode;
		$this->basket = t3lib_div::makeInstance('tx_ttproducts_basket');
		$this->funcTablename = $funcTablename;
		$this->useArticles = $useArticles;
		$this->error_code = &$error_code;

		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($this->cObj);
		$this->urlObj = t3lib_div::makeInstance('tx_ttproducts_url_view'); // a copy of it
		// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
		$this->urlArray = array();
		if ($this->basket->basketExtra['payment.']['handleURL'])	{
			$this->urlArray['form_url_thanks'] = $this->basket->basketExtra['payment.']['handleURL'];
		}
		if ($this->basket->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$this->urlArray['form_url_target'] = $this->basket->basketExtra['payment.']['handleTarget'];
		}
		$this->urlObj->setUrlArray($this->urlArray);
	} // init


	protected function getOrderUid () {
		global $TSFE;

		$result = false;
		$orderUid = 0;
		$orderArray = $TSFE->fe_user->getKey('ses','order');

		if (isset($orderArray['orderUid'])) {
			$orderUid = $orderArray['orderUid'];
			$result = $orderUid;
		}

		if (!$orderUid && count($this->basket->getItemArray())) {
			$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
			$orderObj = $tablesObj->get('sys_products_orders');
			$orderUid = $orderObj->getUid();
			if (!$orderUid)	{
				$orderUid = $orderObj->getBlankUid();
			}
			$result = $orderUid;
		}
		return $result;
	}


    protected function getOrdernumber ($orderUid) {
        $result = '';

        if ($orderUid) {
            $tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
            $orderObj = $tablesObj->get('sys_products_orders');
            $result = $orderObj->getNumber($orderUid);
        }
        return $result;
    }


	/**
	 * returns the activities in the order in which they have to be processed
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function transformActivities ($activities)	{
		$retActivities = array();
		$codeActivities = array();
		$codeActivityArray = array (
			'1' =>
				'products_overview',
				'products_basket',
				'products_info',
				'products_payment',
				'products_customized_payment',
				'products_verify',
				'products_finalize',
		);

		$activityArray =  array (
			'1' =>
			'products_redeem_gift',
			'products_clear_basket'
		);

		if (is_array($activities)) {
			foreach ($codeActivityArray as $k => $activity) {
				if ($activities[$activity]) {
					$codeActivities[$activity] = true;
				}
			}
		}

		if ($codeActivities['products_info']) {
			if($codeActivities['products_payment']) {
				$codeActivities['products_payment'] = false;
			}
		}
		if ($codeActivities['products_basket'] && count($codeActivities)>1) {
			$codeActivities['products_basket'] = false;
		}

		$sortedCodeActivities = array();
        foreach ($codeActivityArray as $activity) { // You must keep the order of activities.
            if (isset($codeActivities[$activity])) {
                $sortedCodeActivities[$activity] = $codeActivities[$activity];
            }
        }
        $codeActivities = $sortedCodeActivities;

		if (is_array($activities)) {
			foreach ($activityArray as $k => $activity) {
				if ($activities[$activity]) {
					$retActivities[$activity] = true;
				}
			}
			$retActivities = array_merge($retActivities, $codeActivities);
		}
		return ($retActivities);
	}


	protected function processPayment (
		$orderUid,
		$orderNumber,
		$cardRow,
		$pidArray,
		$currentPaymentActivity,
		$calculatedArray,
		$basketExtra,
		&$bFinalize,
		&$errorMessage
	)	{
		global $TSFE;

		$content = '';
		$basketView = t3lib_div::makeInstance('tx_ttproducts_basket_view');
		$handleScript = $TSFE->tmpl->getFileName($basketExtra['payment.']['handleScript']);
		$handleLib = $basketExtra['payment.']['handleLib'];
		$infoViewObj = t3lib_div::makeInstance('tx_ttproducts_info_view');

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');

		if ($handleScript)	{
			$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
			$content = $paymentshippingObj->includeHandleScript($handleScript, $basketExtra['payment.']['handleScript.'], $this->conf['paymentActivity'], $bFinalize, $this->pibase, $infoViewObj);
		} else if (strpos($handleLib, 'transactor') !== false && t3lib_extMgm::isLoaded($handleLib))	{
				// Payment Transactor
            $transactorConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$handleLib]);
            $useNewTransactor = false;
            if (
                isset($transactorConf['compatibility']) &&
                $transactorConf['compatibility'] == '0'
            ) {
                $useNewTransactor = true;
            }

            // Get references to the concerning baskets
			$addQueryString = array();
			$excludeList = '';
			$linkParams = $this->urlObj->getLinkParams($excludeList, $addQueryString, true, false);

			$markerArray = array();
            if ($useNewTransactor) {
                $callingClassName = '\\JambageCom\\Transactor\\Api\\Start';
                call_user_func($callingClassName . '::test');

                if (
                    class_exists($callingClassName) &&
                    method_exists($callingClassName, 'init') &&
                    method_exists($callingClassName, 'includeHandleLib')
                ) {
                    call_user_func($callingClassName . '::init', $langObj, $this->cObj, $this->conf);
                    $parameters = array(
                        $handleLib,
                        $basketExtra['payment.']['handleLib.'],
                        TT_PRODUCTS_EXT,
                        $this->basket->getItemArray(),
                        $calculatedArray,
                        $this->basket->recs['delivery']['note'],
                        $this->conf['paymentActivity'],
                        $currentPaymentActivity,
                        $infoViewObj->infoArray,
                        $pidArray,
                        $linkParams,
                        $this->basket->order['orderTrackingNo'],
                        $orderUid,
                        $orderNumber,
                        $this->conf['orderEmail_to'],
                        $cardRow,
                        &$bFinalize,
                        &$bFinalVerify,
                        &$markerArray,
                        &$templateFilename,
                        &$localTemplateCode,
                        &$errorMessage
                    );
                    $content = call_user_func_array(
                        $callingClassName . '::includeHandleLib',
                        $parameters
                    );
                }
            } else {
                tx_transactor_api::init($this->pibase, $this->cObj, $this->conf);
                $content = tx_transactor_api::includeHandleLib(
                    $handleLib,
                    $basketExtra['payment.']['handleLib.'],
                    TT_PRODUCTS_EXT,
                    $this->basket->getItemArray(),
                    $calculatedArray,
                    $this->basket->recs['delivery']['note'],
                    $this->conf['paymentActivity'],
                    $currentPaymentActivity,
                    $infoViewObj->infoArray,
                    $pidArray,
                    $linkParams,
                    $this->basket->order['orderTrackingNo'],
                    $orderUid,
                    $cardRow,
                    $bFinalize,
                    $bFinalVerify,
                    $markerArray,
                    $templateFilename,
                    $localTemplateCode,
                    $errorMessage
                );
            }

			if (!$errorMessage && $content == '' && !$bFinalize && $localTemplateCode != '') {
				$content = $basketView->getView(
					$localTemplateCode,
					'PAYMENT',
					$infoViewObj,
					false,
					false,
					$calculatedArray,
					true,
					'TRANSACTOR_FORM_TEMPLATE',
					$markerArray,
					$templateFilename
				);
			}
		}

		return $content;
	} // processPayment


	public function getErrorLabel (
		$langObj,
		$accountObj,
		$cardObj,
		$pidagb,
		$infoArray,
		$checkRequired,
		$checkAllowed,
		$cardRequired,
		$accountRequired,
		$paymentErrorMsg
	) {
		global $TSFE;

        if ($checkRequired || $checkAllowed) {
            $check = ($checkRequired ? $checkRequired : $checkAllowed);
            $languageKey = '';
            
            if (
                $checkAllowed == 'email'
            ) {
                if (
                    t3lib_extMgm::isLoaded('sr_feuser_register') ||
                    t3lib_extMgm::isLoaded('agency')
                ) {
                    $languageKey = 'evalErrors_email_email';
                } else {
                    $languageKey = 'invalid_email';
                }
            }

			if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
                if (!$languageKey) {
                    $languageKey = 'missing_' . $check;
                }
                $label = $TSFE->sL('LLL:EXT:sr_feuser_register/Resources/Private/Language/locallang.xlf:' . $languageKey);
				$editPID = $TSFE->tmpl->setup['plugin.']['tx_srfeuserregister_pi1.']['editPID'];

				if ($TSFE->loginUser && $editPID) {
					$addParams = array ('products_payment' => 1);
					$addParams = $this->urlObj->getLinkParams('',$addParams,true);
					$srfeuserBackUrl = $this->pibase->pi_getPageLink($TSFE->id,'',$addParams);
					$srfeuserParams = array('tx_srfeuserregister_pi1[backURL]' => $srfeuserBackUrl);
					$addParams = $this->urlObj->getLinkParams('',$srfeuserParams,true);
					$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID,'',$addParams);
				}
			} else if (t3lib_extMgm::isLoaded('agency')) {
                if (!$languageKey) {
                    $languageKey = 'missing_' . $check;
                }
                $label = $TSFE->sL('LLL:EXT:agency/pi/locallang.xml:' . $languageKey);
				$editPID = $TSFE->tmpl->setup['plugin.']['tx_agency.']['editPID'];

				if ($TSFE->loginUser && $editPID) {
					$addParams = array ('products_payment' => 1);
					$addParams = $this->urlObj->getLinkParams('', $addParams, true);
					$agencyBackUrl = $this->pibase->pi_getPageLink($TSFE->id, '', $addParams);
					$agencyParams = array('agency[backURL]' => $agencyBackUrl);
					$addParams = $this->urlObj->getLinkParams('', $agencyParams, true);
					$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID, '', $addParams);
				}
			}

            if (!$label) {
                if ($languageKey) {
                    $label = tx_div2007_alpha5::getLL_fh003($langObj, $languageKey);
                } else {
                    $tmpArray = t3lib_div::trimExplode('|', tx_div2007_alpha5::getLL_fh003($langObj, 'missing'));
                    $languageKey = 'missing_' . $check;
                    $label = tx_div2007_alpha5::getLL_fh003($langObj, $languageKey);
                    if ($label)	{
                        $label = $tmpArray[0] .' '. $label . ' '. $tmpArray[1];
                    } else {
                        $label = 'field: ' . $check;
                    }
                }
            }
		} else if ($pidagb && !$_REQUEST['recs']['personinfo']['agb'] && !t3lib_div::_GET('products_payment') && !$infoArray['billing']['agb']) {
				// so AGB has not been accepted
			$label = tx_div2007_alpha5::getLL_fh003($langObj, 'accept_AGB');

			$addQueryString['agb']=0;
		} else if ($cardRequired)	{
			$label = '*'.tx_div2007_alpha5::getLL_fh003($langObj, $cardObj->getTablename() . '.' . $cardRequired) . '*';
		} else if ($accountRequired)	{
			$label = '*' . tx_div2007_alpha5::getLL_fh003($langObj, $accountObj->getTablename()) . ': ' . tx_div2007_alpha5::getLL_fh003($langObj, $accountObj->getTablename() . '.' . $accountRequired) . '*';
		} else if ($paymentErrorMsg)	{
			$label = $paymentErrorMsg;
		} else {
			$message = tx_div2007_alpha5::getLL_fh003($langObj, 'internal_error');
			$messageArr = explode('|', $message);
			$label = $messageArr[0].'TTP_2'.$messageArr[1].'products_payment'.$messageArr[2];
		}

		return $label;
	}


	public function getContent (
		$mainMarkerArray,
		$calculatedArray,
		$basketExtra,
		$theCode,
		$basket_tmpl,
		$bPayment,
		$orderUid,
		$orderNumber,
		$activityArray,
		$currentPaymentActivity,
		$pidArray,
		$infoArray,
		$checkBasket,
		$basketEmpty,
		$checkRequired,
		$checkAllowed,
		$cardRequired,
		$accountRequired,
		$paymentErrorMsg,
		$pidagb,
		$cardObj,
		$cardRow,
		$accountObj,
		&$markerArray,
		&$errorMessage,
		&$bFinalize
	) {
		global $TSFE;
		global $TYPO3_DB;

		$empty = '';
		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$basketView = t3lib_div::makeInstance('tx_ttproducts_basket_view');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$content = '';

		if ($checkBasket && !$basketEmpty)	{
			$basketConf = $cnf->getBasketConf('minPrice'); // check the basket limits

			foreach ($activityArray as $activity => $valid) {
				if ($valid) {
					$bNeedsMinCheck = in_array($activity, array('products_info','products_payment', 'products_customized_payment',  'products_verify', 'products_finalize', 'unknown'));
				}
				if ($bNeedsMinCheck) {
					break;
				}
			}

			if ($bNeedsMinCheck && $basketConf['type'] == 'price')	{
				$value = $calculatedArray['priceTax'][$basketConf['collect']];
				if (isset($value) && isset($basketConf['collect']) && $value < doubleval($basketConf['value']))	{
					$basket_tmpl = 'BASKET_TEMPLATE_MINPRICE_ERROR';
					$bFinalize = false;
				}
			}
		}

		$basketMarkerArray = array();

		if ($checkBasket && $basketEmpty)	{
			$contentEmpty = '';
			if ($this->activityArray['products_overview']) {
				tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	//
				$contentEmpty = $this->cObj->getSubpart(
					$this->templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY' . $this->config['templateSuffix'] . '###')
				);

				if (!$contentEmpty)	{
					$contentEmpty = $this->cObj->getSubpart(
						$this->templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY###')
					);
				}
			} else if ($this->activityArray['products_basket'] || $this->activityArray['products_info'] || $this->activityArray['products_payment']) {
				$contentEmpty = $this->cObj->getSubpart(
					$this->templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_TEMPLATE_EMPTY' . $this->config['templateSuffix'] . '###')
				);

				if (!$contentEmpty)	{
					$contentEmpty = $this->cObj->getSubpart(
						$this->templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_TEMPLATE_EMPTY###')
					);
				}
			} else if ($this->activityArray['products_finalize'])	{
				// Todo: Neuabsenden einer bereits abgesendeten Bestellung. Der Warenkorb ist schon gelöscht.
				if (!$basketObj->order)	{
					$contentEmpty = tx_div2007_alpha5::getLL_fh003($langObj,  'order_already_finalized');
				}
			}

			if ($contentEmpty != '')	{

				$contentEmpty = $markerObj->replaceGlobalMarkers($contentEmpty);
				$bFinalize = false;
			}
			$content .= $contentEmpty;
			$calculatedArray = $basketObj->getCalculatedArray();
			$basketMarkerArray = $basketView->getMarkerArray($calculatedArray);
			$markerArray = $basketMarkerArray;
		} else if (empty($checkRequired) && empty($checkAllowed) && empty($cardRequired) && empty($accountRequired) && empty($paymentErrorMsg) &&
			(empty($pidagb) ||
			$_REQUEST['recs']['personinfo']['agb'] || ($bPayment && t3lib_div::_GET('products_payment')) || $infoArray['billing']['agb'])) {

            if (
                !$basketEmpty &&
                $bPayment &&
                (
                    $this->conf['paymentActivity'] == 'payment' ||
                    $this->conf['paymentActivity'] == 'verify'
                )
            ) {
				$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
					$this->processPayment(
						$orderUid,
						$orderNumber,
						$cardRow,
						$pidArray,
						$currentPaymentActivity,
						$calculatedArray,
						$basketExtra,
						$bFinalize,
						$errorMessage
					);

                if ($errorMessage != '')	{
					$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
					$markerArray['###ERROR_DETAILS###'] = $errorMessage;
				}
			} else {
				$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
			}

			$paymentHTML = '';
			if (!$bFinalize && $basket_tmpl != '')	{
				$infoViewObj = t3lib_div::makeInstance('tx_ttproducts_info_view');
				$paymentHTML = $basketView->getView(
					$empty,
					$theCode,
					$infoViewObj,
					$this->activityArray['products_info'],
					false,
					$calculatedArray,
					true,
					$basket_tmpl,
					$mainMarkerArray
				);
				$content .= $paymentHTML;
			}

			if ($orderUid && $paymentHTML != '') {

				$orderObj = $tablesObj->get('sys_products_orders');
				$orderObj->setData($orderUid, $paymentHTML, 0);
			}
		} else {	// If not all required info-fields are filled in, this is shown instead:
			$infoArray['billing']['error'] = 1;
			$requiredOut =
				$markerObj->replaceGlobalMarkers(
					$this->cObj->getSubpart(
						$this->templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_REQUIRED_INFO_MISSING###')
					)
				);

            if (!$requiredOut) {
                $templateObj = t3lib_div::makeInstance('tx_ttproducts_template');
                $this->error_code[0] = 'no_subtemplate';
                $this->error_code[1] = '###BASKET_REQUIRED_INFO_MISSING###';
                $this->error_code[2] = $templateObj->getTemplateFile();
                return '';
            }
            $content .= $requiredOut;

			$label = '';
			$label = $this->getErrorLabel(
				$langObj,
				$accountObj,
				$cardObj,
				$pidagb,
				$infoArray,
				$checkRequired,
				$checkAllowed,
				$cardRequired,
				$accountRequired,
				$paymentErrorMsg
			);
			$markerArray['###ERROR_DETAILS###'] = $label;
		}
		return $content;
	} // getContent


	public function processActivities (
		$activityArray,
		$activityVarsArray,
		$codeActivityArray,
		$calculatedArray,
		$basketExtra,
		&$errorMessage
	)	{
		global $TSFE;
		global $TYPO3_DB;

		$basket_tmpl = '';
		$empty = '';
		$content = '';
        $basketEmpty = (count($this->basket->getItemArray()) == 0);

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$basketView = t3lib_div::makeInstance('tx_ttproducts_basket_view');
		$infoViewObj = t3lib_div::makeInstance('tx_ttproducts_info_view');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
		$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');

		$markerArray = array();
		$markerArray['###ERROR_DETAILS###'] = '';

		$pidTypeArray = array('PIDthanks','PIDfinalize','PIDpayment');
		$pidArray = array();
		foreach ($pidTypeArray as $pidType)	{
			if ($cnf->conf[$pidType])	{
				$pidArray[$pidType] = $cnf->conf[$pidType];
			}
		}

		$mainMarkerArray = array();
		$bFinalize = false; // no finalization must be called.

		if ($activityArray['products_info'] || $activityArray['products_payment'] || $activityArray['products_customized_payment'] || $activityArray['products_verify'] || $activityArray['products_finalize'])	{
			// get credit card info
			$cardViewObj = $tablesObj->get('sys_products_cards',true);
			$cardObj = $cardViewObj->getModelObj();
			$cardUid = $cardObj->getUid();
			$cardRow = $cardObj->getRow($cardUid);
			$cardViewObj->getMarkerArray($cardRow, $mainMarkerArray, $cardObj->getAllowedArray(), $cardObj->getTablename());

			// get bank account info
			$accountViewObj = $tablesObj->get('sys_products_accounts', true);
			$accountObj = $accountViewObj->getModelObj();
			$accountViewObj->getMarkerArray($accountObj->acArray, $mainMarkerArray, $accountObj->getIsAllowed());
		}

		foreach ($activityArray as $activity => $value) {
			$theCode = 'BASKET';

			if ($value) {
				$currentPaymentActivity = array_search($activity, $activityVarsArray);
				$activityConf = $cnf->getBasketConf('activity', $currentPaymentActivity);

				if (isset($activityConf['check']))	{
					$checkArray = t3lib_div::trimExplode(',', $activityConf['check']);

					foreach ($checkArray as $checkType)	{

						switch ($checkType)	{
							case 'account':
								if ($paymentshippingObj->useAccount($basketExtra))	{
									$accountRequired = $accountObj->checkRequired();
								}
								break;
							case 'address':
								$checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);
								if (!$checkRequired)	{
									$checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
								}
								$checkAllowed = $infoViewObj->checkAllowed($basketExtra);
								break;
							case 'agb':
								$pidagb = intval($this->conf['PIDagb']);
								break;
							case 'basket':
								$checkBasket = true;
								break;
							case 'card':
								if ($paymentshippingObj->useCreditcard($basketExtra))	{
									$cardRequired = $cardObj->checkRequired();
								}
								break;
						}
					}
				}

					// perform action
				switch($activity)	{
					case 'products_clear_basket':
						// Empties the shopping basket!
						$this->basket->clearBasket(true);
						$calculatedArray = array();
						$calculObj = t3lib_div::makeInstance('tx_ttproducts_basket_calculate');
						$calculObj->setCalculatedArray($calculatedArray);
                        $basketEmpty = (count($this->basket->getItemArray()) == 0);
					break;
					case 'products_basket':
						if (count($activityArray) == 1) {
							$basket_tmpl = 'BASKET_TEMPLATE';
						}
					break;
					case 'products_overview':
						tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	// TODO
						$basket_tmpl = 'BASKET_OVERVIEW_TEMPLATE';

						if ($codeActivityArray[$activity])	{
							$theCode = 'OVERVIEW';
						}
					break;
					case 'products_redeem_gift': 	// this shall never be the only activity
						if (trim($TSFE->fe_user->user['username']) == '') {
							$basket_tmpl = 'BASKET_TEMPLATE_NOT_LOGGED_IN';
						} else {
							$uniqueId = t3lib_div::trimExplode ('-', $this->basket->recs['tt_products']['giftcode'], true);
							$query='uid=\''.intval($uniqueId[0]).'\' AND crdate=\''.intval($uniqueId[1]).'\''.' AND NOT deleted' ;
							$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);
							$row = $TYPO3_DB->sql_fetch_assoc($giftRes);

							$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
							if ($row && $pricefactor > 0) {
								$money = $row['amount'];
								$uid = $row['uid'];
								$fieldsArray = array();
								$fieldsArray['deleted']=1;
									// Delete the gift record
								$TYPO3_DB->exec_UPDATEquery('tt_products_gifts', 'uid='.intval($uid), $fieldsArray);
								$creditpoints = $money / $pricefactor;
								tx_ttproducts_creditpoints_div::addCreditPoints($TSFE->fe_user->user['username'], $creditpoints);
								$cpArray = $TSFE->fe_user->getKey('ses','cp');
								$cpArray['gift']['amount'] += $creditpoints;
								$TSFE->fe_user->setKey('ses','cp',$cpArray);
							}
						}
					break;
					case 'products_info':
						// if (!$activityArray['products_payment'] && !$activityArray['products_finalize']) {
						tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase); // TODO
						$basket_tmpl = 'BASKET_INFO_TEMPLATE';
						// }
					break;
					case 'products_payment':
						$bPayment = true;
						$orderUid = $this->getOrderUid();
						$orderNumber = $this->getOrdernumber($orderUid);

						tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	// TODO
						$pidagb = intval($this->conf['PIDagb']);
						$checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);
						if (!$checkRequired)	{
							$checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
						}
						$checkAllowed = $infoViewObj->checkAllowed($basketExtra);

						if ($paymentshippingObj->useCreditcard($basketExtra))	{
							$cardRequired = $cardObj->checkRequired();
						}

						if ($paymentshippingObj->useAccount($basketExtra))	{
							$accountRequired = $accountObj->checkRequired();
						}

						if ($this->conf['paymentActivity'] == 'payment' || $this->conf['paymentActivity'] == 'verify')	{
							$handleLib = $paymentshippingObj->getHandleLib('request', $basketExtra);
							if (strpos($handleLib,'transactor') !== false)	{
								// Payment Transactor
								tx_transactor_api::init($this->pibase, $this->cObj, $this->conf);
								$referenceId = tx_transactor_api::getReferenceUid(
									$handleLib,
									$this->basket->basketExtra['payment.']['handleLib.'],
									TT_PRODUCTS_EXT,
									$orderUid
								);
								$addQueryString = array();
								$excludeList = '';
								$linkParams = $this->urlObj->getLinkParams($excludeList,$addQueryString,true);
                                $useNewTransactor = false;
                                if (
                                    isset($transactorConf['compatibility']) &&
                                    $transactorConf['compatibility'] == '0'
                                ) {
                                    $useNewTransactor = true;
                                }

                                if ($useNewTransactor) {
                                    $callingClassName = '\\JambageCom\\Transactor\\Api\\Start';

                                    if (
                                        class_exists($callingClassName) &&
                                        method_exists($callingClassName, 'checkRequired')
                                    ) {
                                        $parameters = array(
                                            $referenceId,
                                            $this->basket->basketExtra['payment.']['handleLib'],
                                            $this->basket->basketExtra['payment.']['handleLib.'],
                                            TT_PRODUCTS_EXT,
                                            $calculatedArray,
                                            $this->conf['paymentActivity'],
                                            $pidArray,
                                            $linkParams,
                                            $this->basket->order['orderTrackingNo'],
                                            $orderUid,
                                            $orderNumber, // neu
                                            $this->conf['orderEmail_to'],
                                            $cardRow
                                        );

                                        $paymentErrorMsg = call_user_func_array(
                                            $callingClassName . '::checkRequired',
                                            $parameters
                                        );
                                    }
                                } else {
                                    $paymentErrorMsg = tx_transactor_api::checkRequired(
                                        $referenceId,
                                        $this->basket->basketExtra['payment.']['handleLib'],
                                        $this->basket->basketExtra['payment.']['handleLib.'],
                                        TT_PRODUCTS_EXT,
                                        $calculatedArray,
                                        $this->conf['paymentActivity'],
                                        $pidArray,
                                        $linkParams,
                                        $this->basket->order['orderTrackingNo'],
                                        $orderUid,
                                        $cardRow
                                    );
                                }
							}
						}
						if ($codeActivityArray[$activity])	{
							$theCode = 'PAYMENT';
						}
						$basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
					break;
					// a special step after payment and before finalization needed for some payment methods
					case 'products_customized_payment': // deprecated
					case 'products_verify':
						$bPayment = true;

                        if (
                            !$basketEmpty &&
                            (
                                $this->conf['paymentActivity']=='verify' ||
                                $this->conf['paymentActivity']=='customized' /* deprecated */
                            )
                        ) {
							$orderUid = $this->getOrderUid();

							$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
								$this->processPayment(
									$orderUid,
									$orderNumber,
									$cardRow,
									$pidArray,
									$currentPaymentActivity,
									$calculatedArray,
									$basketExtra,
									$bFinalize,
									$errorMessage
								);

							$paymentErrorMsg = $errorMessage;

							if ($errorMessage != '')	{
								$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
							}
							if (!$bFinalize)	{
								$basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
							}
						} else {
							$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
						}
					break;
					case 'products_finalize':
						$bPayment = true;
						$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');

						$handleLib = $paymentshippingObj->getHandleLib('request', $basketExtra);
						if ($handleLib == '')	{
							$handleLib = $paymentshippingObj->getHandleLib('form', $basketExtra);
						}

						if (
                            !$basketEmpty &&
                            $handleLib != ''
                        ) {
							$orderUid = $this->getOrderUid();
							$orderNumber = $this->getOrdernumber($orderUid);
							$rc = $this->processPayment(
								$orderUid,
								$orderNumber,
								$cardRow,
								$pidArray,
								$currentPaymentActivity,
								$calculatedArray,
								$basketExtra,
								$bFinalize,
								$errorMessage
							);
							$paymentErrorMsg = $errorMessage;

							if($bFinalize == false && $errorMessage != ''){
								$label = $paymentErrorMsg;
								$markerArray['###ERROR_DETAILS###'] = $label;
								$basket_tmpl = 'BASKET_TEMPLATE'; // step back to the basket page
							} else {
								$content = ''; // do not show the content of payment again
							}
						} else {
							$bFinalize = true;
						}
						if ($codeActivityArray[$activity] && $bFinalize)	{
							$theCode = 'FINALIZE';
						}
					break;
					default:
						// nothing yet
						$activity = 'unknown';
					break;
				} // switch
			}	// if ($value)

			if ($value) {
				$newContent = $this->getContent(
					$mainMarkerArray,
					$calculatedArray,
					$basketExtra,
					$theCode,
					$basket_tmpl,
					$bPayment,
					$orderUid,
					$orderNumber,
					$activityArray,
					$currentPaymentActivity,
					$pidArray,
					$infoViewObj->infoArray,
					$checkBasket,
					$basketEmpty,
					$checkRequired,
					$checkAllowed,
					$cardRequired,
					$accountRequired,
					$paymentErrorMsg,
					$pidagb,
					$cardObj,
					$cardRow,
					$accountObj,
					$markerArray,
					$errorMessage,
					$bFinalize
				);

				$addQueryString = array();
				$overwriteMarkerArray = array();
				$overwriteMarkerArray = $this->urlObj->addURLMarkers(0, array(),$addQueryString);
				$markerArray = array_merge($markerArray,$overwriteMarkerArray);
				$content = $this->cObj->substituteMarkerArray($content . $newContent, $markerArray);
			}
		} // foreach ($activityArray as $activity=>$value)

			// finalization at the end so that after every activity this can be called
		if ($bFinalize) {
			$checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);

			if (!$checkRequired)	{
				$checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
			}

			$checkAllowed = $infoViewObj->checkAllowed($basketExtra);
			if ($checkRequired == '' && $checkAllowed == '')	{
				tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	// TODO
				$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
				$orderUid = $this->getOrderUid();
				$orderNumber = $this->getOrdernumber($orderUid);

                if (
                    !$basketEmpty &&
                    trim($this->conf['paymentActivity']) == 'finalize'
                ) {
					$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
						$this->processPayment(
							$orderUid,
							$orderNumber,
							$cardRow,
							$pidArray,
							'finalize',
							$calculatedArray,
							$basketExtra,
							$bFinalize,
							$errorMessage
						);
					if ($errorMessage != '')	{
						$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
					}
				} else {
					$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
				}

				t3lib_div::requireOnce (PATH_BE_ttproducts.'control/class.tx_ttproducts_activity_finalize.php');

					// order finalization
				$activityFinalize = t3lib_div::makeInstance('tx_ttproducts_activity_finalize');
				$orderObj = $tablesObj->get('sys_products_orders');
				$activityFinalize->init(
					$this->pibase,
					$orderObj
				);
				$contentTmp = $activityFinalize->doProcessing(
					$this->templateCode,
					$mainMarkerArray,
					$this->funcTablename,
					$orderUid,
					$errorMessage
				);

				if ($this->conf['PIDthanks'] > 0) {
					$tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
					$contentTmpThanks = $basketView->getView(
						$empty,
						'BASKET',
						$infoViewObj,
						false,
						false,
						$calculatedArray,
						true,
						$tmpl,
						$mainMarkerArray
					);
					if ($contentTmpThanks != '')	{
						$contentTmp = $contentTmpThanks;
					}
				}
				if ($activityArray['products_payment'])	{	// forget the payment output from before if it comes to finalize
					$content = '';
				}
				$content .= $contentTmp;
				$contentNoSave = $basketView->getView(
					$empty,
					'BASKET',
					$infoViewObj,
					false,
					false,
					$calculatedArray,
					true,
					'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE',
					$mainMarkerArray
				);
				$content .= $contentNoSave;

				// Empties the shopping basket!
				$this->basket->clearBasket();
			} else {	// If not all required info-fields are filled in, this is shown instead:
				$requiredOut = $this->cObj->getSubpart(
					$this->templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_REQUIRED_INFO_MISSING###')
				);

				if (!$requiredOut) {
					$templateObj = t3lib_div::makeInstance('tx_ttproducts_template');
					$this->error_code[0] = 'no_subtemplate';
					$this->error_code[1] = '###BASKET_REQUIRED_INFO_MISSING###';
					$this->error_code[2] = $templateObj->getTemplateFile();
					return '';
				}

				$label = $this->getErrorLabel(
					$langObj,
					$accountObj,
					$cardObj,
					$pidagb,
					$infoViewObj->infoArray,
					$checkRequired,
					$checkAllowed,
					$cardRequired,
					$accountRequired,
					$paymentErrorMsg
				);

				$mainMarkerArray['###ERROR_DETAILS###'] = $label;
				$urlMarkerArray = $this->urlObj->addURLMarkers(0, array());
				$markerArray = array_merge($mainMarkerArray, $urlMarkerArray);

				$content .= $requiredOut;
				$content = $this->cObj->substituteMarkerArray(
					$content,
					$markerArray
				);
			}
		}

		$content = $markerObj->replaceGlobalMarkers(
			$content
		);

		return $content;
	} // processActivities


	/**
	 * Do all the things to be done for this activity
	 * former functions products_basket and basketView::printView
	 * Takes care of basket, address info, confirmation and gate to payment
	 * Also the 'products_...' script parameters are used here.
	 *
	 * @param	array		  CODEs for display mode
	 * @return	string	text to display
	 */
	public function doProcessing (&$codes, $calculatedArray, $basketExtra, &$errorMessage) {
		global $TSFE;
		global $TYPO3_DB;

		$content = '';
		$empty = '';
		$activityArray = array();
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$infoViewObj = t3lib_div::makeInstance('tx_ttproducts_info_view');
		$basketView = t3lib_div::makeInstance('tx_ttproducts_basket_view');
		$basketView->init(
			$this->pibaseClass,
			$this->urlArray,
			$this->useArticles,
			$this->templateCode,
			$this->error_code
		);
		$activityVarsArray = array(
			'clear_basket' => 'products_clear_basket',
			'customized_payment' => 'products_customized_payment',
			'basket' => 'products_basket',
			'finalize' => 'products_finalize',
			'info' => 'products_info',
			'overview' => 'products_overview',
			'payment' => 'products_payment',
			'redeem_gift' => 'products_redeem_gift',
			'verify' => 'products_verify'
		);

		$update = t3lib_div::_POST('products_update') || t3lib_div::_POST('products_update_x');
		$info = t3lib_div::_POST('products_info') || t3lib_div::_POST('products_info_x');
		$payment = t3lib_div::_POST('products_payment') || t3lib_div::_POST('products_payment_x');
		$gpVars = t3lib_div::_GP(TT_PRODUCTS_EXT);

		if (!$update && !$payment && !$info && isset($gpVars) && is_array($gpVars) && isset($gpVars['activity']) && is_array($gpVars['activity']))	{
			$changedActivity = key($gpVars['activity']);
			$theActivity = $activityVarsArray[$changedActivity];

			if ($theActivity)	{
				$activityArray[$theActivity] = $gpVars['activity'][$changedActivity];
			}
		}

			// use '_x' for coordinates from Internet Explorer if button images are used
		if (t3lib_div::_GP('products_redeem_gift') || t3lib_div::_GP('products_redeem_gift_x'))    {
		 	$activityArray['products_redeem_gift'] = true;
		}

		if (t3lib_div::_GP('products_clear_basket') || t3lib_div::_GP('products_clear_basket_x'))    {
			$activityArray['products_clear_basket'] = true;
		}
		if (t3lib_div::_GP('products_overview') || t3lib_div::_GP('products_overview_x'))    {
			$activityArray['products_overview'] = true;
		}
		if (!$update) {
			if (t3lib_div::_GP('products_payment') || t3lib_div::_GP('products_payment_x'))    {
				$activityArray['products_payment'] = true;
			} else if (t3lib_div::_GP('products_info') || t3lib_div::_GP('products_info_x'))    {
				$activityArray['products_info'] = true;
			}
		}
		if (t3lib_div::_GP('products_customized_payment') || t3lib_div::_GP('products_customized_payment_x'))    {
			$activityArray['products_customized_payment'] = true;
		}
		if (t3lib_div::_GP('products_verify') || t3lib_div::_GP('products_verify_x'))    {
			$activityArray['products_verify'] = true;
		}
		if (t3lib_div::_GP('products_finalize') || t3lib_div::_GP('products_finalize_x'))    {
			$activityArray['products_finalize'] = true;
		}

		$codeActivityArray = array();
		$bBasketCode = false;
		if (is_array($codes)) {
			foreach ($codes as $k => $code) {
				if ($code == 'BASKET')	{
					$codeActivityArray['products_basket'] = true;
					$bBasketCode = true;
				} elseif ($code == 'INFO') {
                    if (
                        !(
                            $activityArray['products_payment'] ||
                            $activityArray['products_verify'] || $activityArray['products_finalize']
                        )
                    ) {
                        $codeActivityArray['products_info'] = true;
                    }
					$bBasketCode = true;
				} elseif ($code == 'OVERVIEW') {
					$codeActivityArray['products_overview'] = true;
                } elseif ($code == 'PAYMENT') {
                    if (
                        $activityArray['products_finalize']
                    ) {
                        $codeActivityArray['products_finalize'] = true;
                    } else {
                        $codeActivityArray['products_payment'] = true;
                    }
                    if ($activityArray['products_verify']) {
                        $bBasketCode = true;
                    }
                } elseif ($code == 'FINALIZE')  {
                    $codeActivityArray['products_finalize'] = true;
                    if ($activityArray['products_verify']) {
                        $bBasketCode = true;
                    }
                }
			}
		}

		if ($bBasketCode)	{
			$activityArray = array_merge($activityArray, $codeActivityArray);
			$this->activityArray = $this->transformActivities($activityArray);
		} else {
			// only the code activities if there is no code BASKET or INFO set
			$this->activityArray = $codeActivityArray;
		}
		tx_ttproducts_model_activity::setActivityArray($this->activityArray);
		$fixCountry = ($this->activityArray['products_basket'] || $this->activityArray['products_info'] || $this->activityArray['products_payment'] || $this->activityArray['products_verify'] || $this->activityArray['products_finalize'] || $this->activityArray['products_customized_payment']);

		$infoViewObj->init(
			$this->pibase,
			$activityArray['products_payment'],
			$fixCountry,
			$basketExtra
		);

		if (
			$fixCountry &&
			$infoViewObj->checkRequired('billing', $basketExtra) == ''
		) {
			$infoViewObj->mapPersonIntoDelivery();
		}

		if (count($this->activityArray)) {
			$content = $this->processActivities(
				$this->activityArray,
				$activityVarsArray,
				$codeActivityArray,
				$calculatedArray,
				$basketExtra,
				$errorMessage
			);
		}
		return $content;
	} //
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']);
}

