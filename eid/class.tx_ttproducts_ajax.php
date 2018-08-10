<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger (franz@ttproducts.de)
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
 * eID compatible AJAX functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_ajax implements t3lib_Singleton {
	var $taxajax;	// xajax object
	var $conf; 	// conf coming from JavaScript via Ajax

	public function init()	{
		global $TSFE;

		$this->taxajax = t3lib_div::makeInstance('tx_taxajax');

			// Encoding of the response to FE charset
		$this->taxajax->setCharEncoding('UTF-8');
	}


	public function setConf(&$conf)	{
		$this->conf = $conf;
	}


	public function &getConf()	{
		return $this->conf;
	}


	public function main (
		$cObj,
		$urlObj,
		$debug,
		$piVarSingle = 'product',
		$piVarCat = 'cat'
	) {
			// Encoding of the response to utf-8.
		// $this->taxajax->setCharEncoding('utf-8');
			// Do you want messages in the status bar?
		// $this->taxajax->statusMessagesOn();

			// Decode form vars from utf8
		// $this->taxajax->decodeUTF8InputOn();

			// Turn only on during testing
		if ($debug) {
			$this->taxajax->debugOn();
		} else	{
			$this->taxajax->debugOff();
		}
		$this->taxajax->setWrapperPrefix('');


// 		$reqURI = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
// 		$origUrlArray = explode('?', $reqURI);
// 		$urlArray = t3lib_div::explodeUrl2Array($origUrlArray['1'],TRUE);
// 		unset($urlArray['cHash']);
// 		$urlArray['no_cache'] = 1;
// 		$urlArray['eID'] = TT_PRODUCTS_EXT;
// 		$reqURI = t3lib_div::implodeArrayForUrl('',$urlArray);
// 		$reqURI{0} = '?';
// 		$reqURI = $origUrlArray['0'] . $reqURI;


		$addQueryString = array(
			'no_cache' => 1,
			'eID' => TT_PRODUCTS_EXT
		);


		$excludeList = 'cHash';
		$queryString = $urlObj->getLinkParams(
			$excludeList,
			$addQueryString,
			FALSE,
			FALSE,
			$piVarSingle,
			$piVarCat
		);

		$linkConf = array('useCacheHash' => 0);
		$target = '';
		$reqURI = tx_div2007_alpha5::getTypoLink_URL_fh003(
			$cObj,
			$GLOBALS['TSFE']->id,
			$queryString,
			$target,
			$linkConf
		);

		$this->taxajax->setRequestURI($reqURI);
	}
}


?>
