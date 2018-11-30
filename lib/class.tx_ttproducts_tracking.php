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
 * tracking functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_tracking implements t3lib_Singleton {
	var $cObj;
	var $conf;		  // original configuration
	private $statusCodeArray;


	/**
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	function init ($cObj) {
		global $TSFE;

		$this->cObj = $cObj;
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;

		if (isset($this->conf['statusCodes.']) && is_array($this->conf['statusCodes.'])) {
			foreach ($this->conf['statusCodes.'] as $k => $v) {
				if (
					tx_div2007_core::testInt($k)
				) {
					$statusCodeArray[$k] = $v;
				}
			}
		} elseif ($this->conf['statusCodesSource']) {

			switch ($this->conf['statusCodesSource']) {
				case 'marker_locallang':
					$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
					$langArray = $markerObj->getLangArray();
					if (is_array($langArray)) {
						$statusMessage = 'tracking_status_message_';
						$len = strlen($statusMessage);
						foreach ($langArray as $k => $v) {
							if (($pos = strpos($k, $statusMessage))===0) {
								$rest = substr($k, $len);
								if (
									tx_div2007_core::testInt($rest)
								) {
									$statusCodeArray[$rest] = $v;
								}
							}
						}
					}
				break;
			}
		}

		$this->setStatusCodeArray($statusCodeArray);
	}


	function setStatusCodeArray (&$statusCodeArray) {
		$this->statusCodeArray = $statusCodeArray;
	}


	function getStatusCodeArray () {
		return $this->statusCodeArray;
	}


	protected function getDate ($newData) {
		$date = '';
		if ($newData) {
			$dateArray = t3lib_div::trimExplode('-', $newData);
			$date = mktime(0, 0, 0, $dateArray[1], $dateArray[0], $dateArray[2]);
		} else {
			$date = time();
		}
		return $date;
	}


	/* search the order status for paid and closed */
	function searchOrderStatus ($status_log,&$orderPaid, &$orderClosed) {
		$orderPaid = FALSE;
		$orderClosed = FALSE;
		if (isset($status_log) && is_array($status_log)) {
			foreach($status_log as $key=>$val) {
				if ($val['status'] == 13) {// Numbers 13 means order has been payed
					$orderPaid = TRUE;
				}
				if ($val['status'] >= 100) {// Numbers 13 means order has been payed
					$orderClosed = TRUE;
					break;
				}
			}
		}
	}

	/*
		Tracking information display and maintenance.

		status-values are
			0:  Blank order
		1-1 Incoming orders
			1:  Order confirmed at website
		2-49: Useable by the shop admin
			2 = Order is received and accepted by store
			10 = Shop is awaiting goods from third-party
			11 = Shop is awaiting customer payment
			12 = Shop is awaiting material from customer
			13 = Order has been payed
			20 = Goods shipped to customer
			21 = Gift certificates shipped to customer
			30 = Other message from store
			...
		50-99:  Useable by the customer
		50-59: General user messages, may be updated by the ordinary users.
			50 = Customer request for cancelling
			51 = Message from customer to shop
		60-69:  Special user messages by the customer
			60 = Send gift certificate message to receiver

		100-299:  Order finalized.
			100 = Order shipped and closed
			101 = Order closed
			200 = Order cancelled

		All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
	*/
	function getTrackingInformation ($orderRow, $templateCode, $trackingCode, $updateCode, &$orderRecord, $admin) {
		global $TSFE, $TYPO3_DB;

		$bUseXHTML = $GLOBALS['TSFE']->config['config']['xhtmlDoctype'] != '';

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$orderObj = $tablesObj->get('sys_products_orders');
		$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
		$pibaseObj = t3lib_div::makeInstance('tx_ttproducts_pi1_base');
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
		$basketView = t3lib_div::makeInstance('tx_ttproducts_basket_view');
		$infoViewObj = t3lib_div::makeInstance('tx_ttproducts_info_view');
		$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
		$theTable = 'sys_products_orders';

		$statusCodeArray = array();

		$allowUpdateFields = array('email', 'email_notify', 'status', 'status_log');
		$newData = $pibaseObj->piVars['data'];
		$bStatusValid = FALSE;

		if (isset($orderRow) && is_array($orderRow) && $orderRow['uid']) {
			$statusCodeArray = $this->getStatusCodeArray();
			$pageTitle = $orderRow['uid'].' ('.$orderRow['bill_no'].'): '.$orderRow['name'].'-'.$orderRow['zip'].'-'.$orderRow['city'].'-'.$orderRow['country'];

// 			$GLOBALS['TSFE']->content = preg_replace('/<title>.+<\/title>/', '<title>' . $titleStr . '</title>', $GLOBALS['TSFE']->content);

			$GLOBALS['TSFE']->page['title'] = $pageTitle;
			$GLOBALS['TSFE']->indexedDocTitle = $pageTitle;

				// Initialize update of status...
			$fieldsArray = array();
			if (isset($orderRecord['email_notify'])) {
				$fieldsArray['email_notify'] = $orderRecord['email_notify'];
				$orderRow['email_notify'] = $orderRecord['email_notify'];
			}
			if (isset($orderRecord['email'])) {
				$fieldsArray['email'] = $orderRecord['email'];
				$orderRow['email'] = $orderRecord['email'];
			}

			if (is_array($orderRecord['status']) && isset($statusCodeArray) && is_array($statusCodeArray)) {
				$bStatusValid = TRUE;
				$status_log = unserialize($orderRow['status_log']);
				$update=0;
				$count=0;
				foreach($orderRecord['status'] as $val) {

					if (!isset($statusCodeArray[$val])) {

						$bStatusValid = FALSE;
						break;
					}

					$status_log_element = array(
						'time' => time(),
						'info' => $statusCodeArray[$val],
						'status' => $val,
						'comment' => ($count == 0 ? $orderRecord['status_comment'].($newData != '' ? '|'.$newData : '') : ''), // comment is inserted only to the fist status
					);

					if ($admin) {

						if ($newData) {
							if ($val >= 31 && $val <= 32) {// Numbers 31,32 are for storing of bill no. of external software
								$fieldsArray['bill_no'] = $newData;
							}
						}

						if ($val == 13) {// Number 13 is that order has been paid. The date muss be entered in format dd-mm-yyyy
							$date = $this->getDate($newData);
							$payMode = '1';

							if (isset($orderRow) && is_array($orderRow) && $orderRow['uid']) {
								$basketRec = $paymentshippingObj->getBasketRec($orderRow);
								$basketExtra = $paymentshippingObj->getBasketExtras($basketRec);
								if (isset($basketExtra) && is_array($basketExtra) && isset($basketExtra['payment.']) && isset($basketExtra['payment.']['mode'])) {
									$modeText = $basketExtra['payment.']['mode'];
									$colName = 'pay_mode';
									$textSchema = $theTable . '.' . $colName . '.I.';
									$i = 0;
									do {
										$text = tx_div2007_alpha5::getLL_fh003(
											$langObj,
											$textSchema . $i,
											$usedLang = 'default'
										);

										$text = str_replace(' ', '_', $text);
										if ($text == $modeText) {
											$payMode = $i;
											break;
										}
										$i++;
									} while ($text != '' && $i < 99);

								}
							}

							$fieldsArray['date_of_payment'] = $date;
							$fieldsArray['pay_mode'] = $payMode;
						}

						if ($val == 20) {// Number 20 is that items have been shipped. The date muss be entered in format dd-mm-yyyy
							$date = $this->getDate($newData);
							$fieldsArray['date_of_delivery'] = $date;
						}
					}


					if ($admin || ($val>=50 && $val<59)) {// Numbers 50-59 are usermessages.
						$recipient = $this->conf['orderEmail_to'];
						if ($orderRow['email'] && ($orderRow['email_notify'])) {
							$recipient .= ','.$orderRow['email'];
						}
						$templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
						$feusersObj = $tablesObj->get('fe_users', TRUE);
						tx_ttproducts_email_div::sendNotifyEmail(
							$this->cObj,
							$this->conf,
							$this->config,
							$feusersObj,
							$orderObj->getNumber($orderRow['uid']),
							$recipient,
							$status_log_element,
							$statusCodeArray,
							t3lib_div::_GP('tracking'),
							$orderRow,
							$templateCode,
							$templateMarker
						);
						$status_log[] = $status_log_element;
						$update=1;
					} else if ($val>=60 && $val<69) { //  60 -69 are special messages
						$templateMarker = 'TRACKING_EMAIL_GIFTNOTIFY_TEMPLATE';
						$query = 'ordernumber=\''.intval($orderRow['uid']).'\'';
						$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);
						while ($giftRow = $TYPO3_DB->sql_fetch_assoc($giftRes)) {
							$recipient = $giftRow['deliveryemail'].','.$giftRow['personemail'];
							tx_ttproducts_email_div::sendGiftEmail(
								$this->cObj,
								$this->conf,
								$recipient,
								$orderRecord['status_comment'],
								$giftRow,
								$templateCode,
								$templateMarker,
								$this->conf['orderEmail_htmlmail']
							);
						}
						$status_log[] = $status_log_element;
						$update=1;
						$TYPO3_DB->sql_free_result($giftRes);
					}
					$count++;
				}
				if ($update)	{
					$fieldsArray['status_log'] = serialize($status_log);
					$fieldsArray['status'] = intval($status_log_element['status']);
				}
			}

			if (count($fieldsArray)) {		// If any items in the field array, save them
				if (!$admin) {	// only these fields may be updated in an already stored order
					$fieldsArray = array_intersect_key($fieldsArray, array_flip($allowUpdateFields));
				}

				if (count($fieldsArray)) {
					$fieldsArray['tstamp'] = time();
					$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderRow['uid']), $fieldsArray);
					$orderRow = $orderObj->getRecord($orderRow['uid']);
				}
			}
			$status_log = unserialize($orderRow['status_log']);
			$orderData = unserialize($orderRow['orderData']);

			if ($orderData === FALSE) {
				$orderData = tx_div2007_alpha5::unserialize_fh002($orderRow['orderData'],FALSE);
			}
		}

			// Getting the template stuff and initialize order data.
		$template=$this->cObj->getSubpart($templateCode,'###TRACKING_DISPLAY_INFO###');
		$this->searchOrderStatus($status_log, $orderPaid, $orderClosed);
		$globalMarkerArray = &$markerObj->getGlobalMarkerArray();

		// making status code 60 disappear if the order has not been payed yet
		if (!$orderPaid || $orderClosed) {
				// Fill marker arrays
			$markerArray = $globalMarkerArray;
			$subpartArray=Array();
			$subpartArray['###STATUS_CODE_60###'] = '';

			$template = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpartArray);
		}

			// Status:
		$STATUS_ITEM = $this->cObj->getSubpart($template,'###STATUS_ITEM###');
		$STATUS_ITEM_c='';
		if (is_array($status_log)) {
			foreach($status_log as $k => $v) {
				$markerArray=Array();
				$markerArray['###ORDER_STATUS_TIME###'] = $this->cObj->stdWrap($v['time'],$this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###'] = $v['status'];
				$info = $statusCodeArray[$v['status']];
				$markerArray['###ORDER_STATUS_INFO###'] = ($info ? $info : $v['info']);
				$markerArray['###ORDER_STATUS_COMMENT###'] = nl2br($v['comment']);

				$STATUS_ITEM_c .= $this->cObj->substituteMarkerArrayCached($STATUS_ITEM, $markerArray);
			}
		}

		$markerArray=$globalMarkerArray;
		$subpartArray=array();
		$wrappedSubpartArray=array();
		$markerArray['###OTHER_ORDERS_OPTIONS###'] = '';
		$markerArray['###STATUS_OPTIONS###'] = '';
		$subpartArray['###STATUS_ITEM###']=$STATUS_ITEM_c;

			// Display admin-interface if access.
		if (!$TSFE->beUserLogin) {
			$subpartArray['###ADMIN_CONTROL###']='';
		} elseif ($admin) {
			$subpartArray['###ADMIN_CONTROL_DENY###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL_OK###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL###']='';
		} else {
			$subpartArray['###ADMIN_CONTROL_OK###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL_DENY###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL###']='';
		}
		$markerFieldArray = array();
		$orderView = $tablesObj->get('sys_products_orders', TRUE);
		$orderObj = $orderView->getModelObj();
		$orderMarkerArray = $globalMarkerArray;
		$viewTagArray = array();
		$parentArray = array();
		$t = array();
		$t['orderFrameWork'] = $this->cObj->getSubpart($template,'###ORDER_ITEM###');

		$fieldsArray = $markerObj->getMarkerFields(
			$t['orderFrameWork'],
			$orderObj->getTableObj()->tableFieldArray,
			$orderObj->getTableObj()->requiredFieldArray,
			$markerFieldArray,
			$orderObj->marker,
			$viewTagArray,
			$parentArray
		);

		if ($orderRow) {
			$orderView->getRowMarkerArray (
				$orderRow,
				'',
				$orderMarkerArray,
				$tmp=array(),
				$tmp=array(),
				$viewTagArray,
				'TRACKING',
				$tmp=array()
			);

			$subpartArray['###ORDER_ITEM###'] = $this->cObj->substituteMarkerArrayCached($t['orderFrameWork'], $orderMarkerArray);
		} else {
			$subpartArray['###ORDER_ITEM###'] = '';
		}

		//
		if ($admin) {
				// Status admin:
			if (isset($statusCodeArray) && is_array($statusCodeArray)) {
				foreach($statusCodeArray as $k => $v) {
					if ($k!=1) {
						$markerArray['###STATUS_OPTIONS###'] .= '<option value="' . $k . '">' . htmlspecialchars($k . ': ' . $v) . '</option>';
					}
				}
			}
			$priceViewObj = t3lib_div::makeInstance('tx_ttproducts_field_price_view');

			if (isset($this->conf['tracking.']) && isset($this->conf['tracking.']['fields']))	{
				$fields = $this->conf['tracking.']['fields'];
			} else {
				$fields = 'uid';
			}
			$fields .= ',' . 'crdate,tracking_code,status,status_log,bill_no,name,amount,feusers_uid';
			$fields = t3lib_div::uniqueList($fields);
			$history = array();
			$fieldMarkerArray = array();
			$oldMode = preg_match('/###OTHER_ORDERS_OPTIONS###\s*<\/select>/i', $templateCode);
			$where = 'status!=0 AND status<100';
			$orderBy = 'crdate';

			if (isset($this->conf['tracking.']) && isset($this->conf['tracking.']['sql.'])) {
				if (isset($this->conf['tracking.']['sql.']['where']))	{
					$where = $this->conf['tracking.']['sql.']['where'];
				}
				if (isset($this->conf['tracking.']['sql.']['orderBy']))	{
					$orderBy = $this->conf['tracking.']['sql.']['orderBy'];
				}
			}

			$bUseHistoryMarkers = (strpos($orderBy, 'crdate') !== FALSE);
			$bInverseHistory = (strpos($orderBy, 'crdate desc') !== FALSE);

			if ($bInverseHistory)	{
				$orderBy = 'crdate'; // Todo: all order by fields must be reversed to keep the history program logic
			}

				// Get unprocessed orders.
			$res = $TYPO3_DB->exec_SELECTquery($fields, 'sys_products_orders', $where . $this->cObj->enableFields('sys_products_orders'), '', $orderBy);

			$valueArray = array();
			$keyMarkerArray = array();
			while(($row = $TYPO3_DB->sql_fetch_assoc($res))) {
				$tmpStatuslog = unserialize($row['status_log']);
				$classPrefix = str_replace('_','-',$pibaseObj->prefixId);
				$this->searchOrderStatus($tmpStatuslog, $tmpPaid, $tmpClosed);
				$class = ($tmpPaid ? $classPrefix.'-paid' : '');
				$class = ($class ? $class.' ' : '' ) . ($tmpClosed ? $classPrefix.'-closed' : '');
				$class = ($class ? ' class="'.$class.'"' : '');

				$fieldMarkerArray['###OPTION_CLASS###'] = $class;

				if ($oldMode) {
					$markerArray['###OTHER_ORDERS_OPTIONS###'] .=
						'<option ' . $class . ' value="' . $row['tracking_code'] . '"' . ($row['uid'] == $orderRow['uid'] ? 'selected="selected"' : '') . '>'.
							htmlspecialchars($row['uid'].' ('.$row['bill_no'].'): '.
								$row['name'] . ' (' . $priceViewObj->priceFormat($row['amount']) . ' ' . $this->conf['currencySymbol'] . ') /' . $row['status']
							) .
						'</option>';
				} else {
					if (isset($row['feusers_uid']) && $bUseHistoryMarkers) {
						if(!$row['feusers_uid'] || !isset($history[$row['feusers_uid']])) {
							$history[$row['feusers_uid']] = array(
								'out' => '',
								'count' => 0,
							);
						}
						$history[$row['feusers_uid']]['count'] += 1;
						$last_order = $history[$row['feusers_uid']];

						if($last_order['count'] == 1) {
							$fieldMarkerArray['###LAST_ORDER_TYPE###'] = tx_div2007_alpha5::getLL_fh003($langObj, 'first_order');
							$fieldMarkerArray['###LAST_ORDER_COUNT###'] = '-';
						} else {
							$fieldMarkerArray['###LAST_ORDER_TYPE###'] = $last_order['out'];
							$fieldMarkerArray['###LAST_ORDER_COUNT###'] = $last_order['count'];
						}
						if($row['feusers_uid'] == 0) {
							$fieldMarkerArray['###LAST_ORDER_TYPE###'] = tx_div2007_alpha5::getLL_fh003($langObj, 'unregistered');
							$fieldMarkerArray['###LAST_ORDER_COUNT###'] = '-';
						}
						if($row['company'] == '') {
							$row['company'] = tx_div2007_alpha5::getLL_fh003($langObj, 'undeclared');
						}
						$history[$row['feusers_uid']]['out'] = date('d.m.Y - H:i', $row['crdate']);
					}

					$fieldMarkerArray['###OPTION_SELECTED###'] = ($row['uid'] == $orderRow['uid'] ? 'selected="selected"' : '');
					foreach ($row as $field => $value) {
						switch ($field) {
							case 'amount':
								$value = $priceViewObj->priceFormat($value);
							break;
							case 'crdate':
								$value = date('d.m.Y - H:i', $value);
							break;
							default:
								$value = htmlspecialchars($value);
							break;
						}
						$fieldMarkerArray['###ORDER_' . strtoupper($field) . '###'] = $value;
					}
					$fieldMarkerArray['###CUR_SYM###'] = $this->conf['currencySymbol'];
					$valueArray[$row['tracking_code']] = $row['uid'];
					$keyMarkerArray[$row['tracking_code']] = $fieldMarkerArray;
				}
			}
			$TYPO3_DB->sql_free_result($res);

			if (!$oldMode) {
// checks if t3jquery is loaded
				if (t3lib_extMgm::isLoaded('t3jquery')) {
					require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');
				}
				// if t3jquery is loaded and the custom Library had been created
				if (T3JQUERY === TRUE) {
					tx_t3jquery::addJqJS();
				} else if ($this->conf['pathToJquery'] != '')	{
				// if none of the previous is true, you need to include your own library
				// just as an example in this way
					$GLOBALS['TSFE']->additionalHeaderData[$ext_key] = '<script src="' . t3lib_div::getFileAbsFileName($this->conf['pathToJquery']) . '" type="text/javascript"></script>';
				}

				if ($bInverseHistory)	{
					$valueArray = array_reverse($valueArray);
				}
				if (isset($this->conf['tracking.'])) {
					$type = $this->conf['tracking.']['recordType'];
					$recordLine = $this->conf['tracking.']['recordLine'];
				}
				if ($type == '') {
					$type = 'select';
				}
				if ($recordLine == '') {
					$recordLine = '<!-- ###INPUT### begin -->###ORDER_UID### (###ORDER_BILL_NO###): ###ORDER_NAME### (###ORDER_AMOUNT### ###CUR_SYM###) / ###ORDER_STATUS###) ###ORDER_CRDATE### ###LAST_ORDER_TYPE### ###LAST_ORDER_COUNT###<!-- ###INPUT### end -->';
				}

				$out = tx_ttproducts_form_div::createSelect(
					$langObj,
					$valueArray,
					'tracking',
					$orderRow['tracking_code'],
					FALSE,
					FALSE,
					array(),
					$type,
					array(),
					$recordLine,
					'',
					$keyMarkerArray
				);

				if (isset($this->conf['tracking.']) && isset($this->conf['tracking.']['recordBox.'])) {
					$out = $this->cObj->stdWrap($out, $this->conf['tracking.']['recordBox.']);
				}
				$markerArray['###OTHER_ORDERS_OPTIONS###'] .= $out;
			}
		}
		$bHasTrackingTemplate = preg_match('/###TRACKING_TEMPLATE###/i', $templateCode);

			// Final things
		if (!$bHasTrackingTemplate) {
			$markerArray['###ORDER_HTML_OUTPUT###'] = $orderData['html_output'];	// The save order-information in HTML-format
		} else if (isset($orderRow) && is_array($orderRow) && $orderRow['uid']) {
			$itemArray = $orderObj->getItemArray($orderRow, $calculatedArray, $infoArray);
			$infoViewObj->init2($infoArray);
			$basketRec = $paymentshippingObj->getBasketRec($orderRow);
			$basketExtra = $paymentshippingObj->getBasketExtras($basketRec);
			$orderArray = array();
			$orderArray['orderTrackingNo'] = $trackingCode;
			$orderArray['orderUid'] = $orderRow['uid'];
			$orderArray['orderDate'] = $orderRow['crdate'];

			if ($orderRow['ac_uid']) {
				// get bank account info
				$accountViewObj = $tablesObj->get('sys_products_accounts', TRUE, FALSE);
				$accountObj = $tablesObj->get('sys_products_accounts', FALSE, FALSE);
				$accountRow = $accountObj->getRow($orderRow['ac_uid']);
				$accountViewObj->getMarkerArray($accountRow, $globalMarkerArray, TRUE);
			}

			if ($orderRow['cc_uid']) {
				$cardViewObj = $tablesObj->get('sys_products_cards', TRUE, FALSE);
				$cardObj = $tablesObj->get('sys_products_cards', FALSE, FALSE);
				$cardRow = $cardObj->getRow($orderRow['cc_uid']);
				$cardViewObj->setCObj($this->cObj);
				$cardViewObj->setConf($this->conf);
				$cardViewObj->getMarkerArray($cardRow, $globalMarkerArray, array());
			}
			$customerEmail = $orderRow['email']; // $infoViewObj->getCustomerEmail();
			$globalMarkerArray['###CUSTOMER_RECIPIENTS_EMAIL###'] = $customerEmail;

			$markerArray['###ORDER_HTML_OUTPUT###'] =
				$basketView->getView(
					$templateCode,
					'TRACKING',
					$infoViewObj,
					FALSE,
					FALSE,
					$calculatedArray,
					TRUE,
					'TRACKING_TEMPLATE',
					$globalMarkerArray,
					'',
					$itemArray,
					$orderArray,
					$basketExtra
				);
		} else {
			$markerArray['###ORDER_HTML_OUTPUT###'] = '';
		}

		if (isset($orderData) && is_array($orderData)) {
			$markerArray['###ORDERCONFIRMATION_HTML_OUTPUT###'] = $orderData['html_output'];	// The save order-information in HTML-format
		} else {
			$markerArray['###ORDERCONFIRMATION_HTML_OUTPUT###'] = '';
		}

		$checkedHTML = ($bUseXHTML ? 'checked="checked"' : 'checked');
		$markerArray['###FIELD_EMAIL_NOTIFY###'] = $orderRow['email_notify'] ? ' ' . $checkedHTML : '';

		$markerArray['###FIELD_EMAIL###'] = $orderRow['email'];
		$markerArray['###ORDER_UID###'] = $orderObj->getNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###TRACKING_NUMBER###'] =  $trackingCode;
		$markerArray['###UPDATE_CODE###'] = $updateCode;
		$markerArray['###TRACKING_DATA_NAME###'] = $pibaseObj->prefixId . '[data]';
		$markerArray['###TRACKING_DATA_VALUE###'] = ($bStatusValid ? '' : $newData);
		$markerArray['###TRACKING_STATUS_COMMENT_NAME###'] = 'orderRecord[status_comment]';
		$markerArray['###TRACKING_STATUS_COMMENT_VALUE###'] = ($bStatusValid ? '' : $orderRecord['status_comment']);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking'] as $classRef) {
				$hookObj= t3lib_div::makeInstance($classRef);
				if (method_exists($hookObj, 'getTrackingInformation')) {
					$hookObj->getTrackingInformation($this, $orderRow, $templateCode, $trackingCode, $updateCode, $orderRecord, $admin, $template, $markerArray, $subpartArray);
				}
			}
		}

		$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $content;
	} // getTrackingInformation
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking.php']);
}



