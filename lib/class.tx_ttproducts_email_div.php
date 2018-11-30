<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
 * email functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_email_div {


	/**
	 * Extended mail function
	 */
	static public function send_mail (
		$toEMail,
		$subject,
		$message,
		$html,
		$fromEMail,
		$fromName,
		$attachment = '',
		$bcc = '',
		$returnPath = ''
	) {
		global $TYPO3_CONF_VARS;

		if ($toEMail == '' || $fromEMail == '' || ($html == '' && $message == '')) {

			return FALSE;
		}

		$result = TRUE;

        if (!is_array($toEMail)) {
            $emailArray = t3lib_div::trimExplode(',', $toEMail);

            $toEMail = array();
            foreach ($emailArray as $email) {
                $toEMail[] = $email;
            }
        }

        $fromName = str_replace('"', '\'', $fromName);

        $mailMessage = tx_div2007_core::newMailMessage();
        $mailMessage->setCharset('UTF-8')
            ->setTo($toEMail)
            ->setFrom(array($fromEMail => $fromName))
            ->setReturnPath($returnPath)
            ->setSubject($subject)
            ->setBody($html, 'text/html')
            ->addPart($message, 'text/plain');

        $type = $mailMessage->getHeaders()->get('Content-Type');
        $type->setParameter('charset', 'UTF-8');

        if (isset($attachment)) {
            if (is_array($attachment)) {
                $attachmentArray = $attachment;
            } else {
                $attachmentArray = array($attachment);
            }
            foreach ($attachmentArray as $theAttachment) {
                if (file_exists($theAttachment)) {
                    $mailMessage->attach(Swift_Attachment::fromPath($theAttachment));
                }
            }
        }

        if ($bcc != '') {
            $mailMessage->addBcc($bcc);
        }
        $mailMessage->send();
        $result = $mailMessage->isSent();
        if ($conf['errorLog'] && count($mailMessage->getFailedRecipients())) {
            error_log('send_mail Pos 2 undelivered emails: ' . implode(',', $mailMessage->getFailedRecipients()) . chr(13), 3, $conf['errorLog']);
        }
        return $result;
	}


	/**
	 * Send notification email for tracking
	 */
	static public function sendNotifyEmail (
		$cObj,
		&$conf,
		&$config,
		&$feusersObj,
		$orderNumber,
		$recipient,
		$v,
		$statusCodeArray,
		$tracking,
		$orderRow,
		$templateCode,
		$templateMarker,
		$sendername='',
		$senderemail=''
	) {
		global $TSFE;

			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);
		$sendername = ($sendername ? $sendername : $conf['orderEmail_fromName']);
		$senderemail = ($senderemail ? $senderemail : $conf['orderEmail_from']);

			// Notification email
		$recipients = $recipient;
		$recipients = t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients)) {	// If any recipients, then compile and send the mail.
			$emailContent=trim($cObj->getSubpart($templateCode,'###'.$templateMarker.$config['templateSuffix'].'###'));
			if (!$emailContent)	{
				$emailContent=trim($cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			}
			if ($emailContent)  {		// If there is plain text content - which is required!!
				$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
				$globalMarkerArray = &$markerObj->getGlobalMarkerArray();

				$markerArray = $globalMarkerArray;
				$markerArray['###ORDER_STATUS_TIME###'] = $cObj->stdWrap($v['time'],$conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###'] = $v['status'];
				$info = $statusCodeArray[$v['status']];
				$markerArray['###ORDER_STATUS_INFO###'] = ($info ? $info : $v['info']);
				$markerArray['###ORDER_STATUS_COMMENT###'] = $v['comment'];
				$markerArray['###PID_TRACKING###'] = $conf['PIDtracking'];
				$markerArray['###PERSON_NAME###'] = $orderData['billing']['name'];
				$markerArray['###DELIVERY_NAME###'] = $orderData['delivery']['name'];

				$feusersObj->getAddressMarkerArray(
					$orderData['billing'],
					$markerArray,
					FALSE,
					'person'
				);
				$feusersObj->getAddressMarkerArray(
					$orderData['delivery'],
					$markerArray,
					FALSE,
					'delivery'
				);

				$markerArray['###ORDER_TRACKING_NO###'] = $tracking;
				$markerArray['###ORDER_UID###'] = $orderNumber;
				$emailContent = $cObj->substituteMarkerArrayCached($emailContent, $markerArray);
				$parts = explode(chr(10),$emailContent,2);
				$subject = trim($parts[0]);
				$plain_message = trim($parts[1]);
				self::send_mail(implode($recipients,','), $subject, $plain_message, $tmp='', $senderemail, $sendername);
			}
		}
	}


	/**
	 * Send notification email for gift certificates
	 */
	static public function sendGiftEmail ($cObj,&$conf,$recipient,$comment,$giftRow,$templateCode,$templateMarker, $bHtmlMail=false)	{
		global $TSFE;

		$sendername = ($giftRow['personname'] ? $giftRow['personname'] : $conf['orderEmail_fromName']);
		$senderemail = ($giftRow['personemail'] ? $giftRow['personemail'] : $conf['orderEmail_from']);
		$recipients = $recipient;
		$recipients = t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients)) {	// If any recipients, then compile and send the mail.
			$emailContent=trim($cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			if ($emailContent)  {		// If there is plain text content - which is required!!
				$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
				$globalMarkerArray = &$markerObj->getGlobalMarkerArray();
				$priceViewObj = t3lib_div::makeInstance('tx_ttproducts_field_price_view');

				$parts = explode(chr(10),$emailContent,2);	// First line is subject
				$subject = trim($parts[0]);
				$plain_message = trim($parts[1]);

				$markerArray = $globalMarkerArray;

				$markerArray['###CERTIFICATES_TOTAL###'] = $priceViewObj->priceFormat($giftRow['amount']);
				$markerArray['###CERTIFICATES_UNIQUE_CODE###'] = $giftRow['uid'].'-'.$giftRow['crdate'];
				$markerArray['###PERSON_NAME###'] = $giftRow['personname'];
				$markerArray['###DELIVERY_NAME###'] = $giftRow['deliveryname'];
				$markerArray['###ORDER_STATUS_COMMENT###'] = $giftRow['note'].($bHtmlMail?'\n':chr(13)).$comment;
				$emailContent = $cObj->substituteMarkerArrayCached($plain_message, $markerArray);

				$recipients = implode($recipients,',');

				if ($bHtmlMail) {	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell = $cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent = $cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$emailContent);
					$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
					$HTMLmailContent=$cObj->substituteMarkerArray($HTMLmailContent, $markerObj->getGlobalMarkerArray());

					self::send_mail($recipients,  $subject, $emailContent, $HTMLmailContent, $senderemail, $sendername, $conf['GiftAttachment']);
				} else {		// ... else just plain text...
					self::send_mail($recipients, $subject, $emailContent, $tmp='',$senderemail, $sendername, $conf['GiftAttachment']);
				}
			}
		}
	}
}



