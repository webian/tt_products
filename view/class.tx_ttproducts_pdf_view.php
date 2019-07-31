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
 * functions for the creation of PDF files
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_pdf_view implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * generates the bill as a PDF file
	 *
	 * @param	string		reference to an item array with all the data of the item
	 * @return	string / boolean	returns the absolute filename of the PDF bill or false
	 * 		 			for the tt_producst record, $row
	 * @access private
	 */
	public function generate (
		$cObj,
		$header,
		$body,
		$footer,
		$absFileName
	) {
		$result = false;
		$charset = 'UTF-8';
		if (
            isset($GLOBALS['TSFE']->renderCharset) &&
            $GLOBALS['TSFE']->renderCharset != ''
        ) {
            $charset = $GLOBALS['TSFE']->renderCharset;
        }

		if (t3lib_extMgm::isLoaded('fpdf')) {
			$csConvObj = $GLOBALS['TSFE']->csConvObj;
			$header = $csConvObj->conv(
				$header,
				$charset,
				'iso-8859-1'
			);

			$body = $csConvObj->conv(
				$body,
				$charset,
				'iso-8859-1'
			);

			$footer = $csConvObj->conv(
				$footer,
				$charset,
				'iso-8859-1'
			);

			GeneralUtility::requireOnce(PATH_BE_ttproducts . 'model/class.tx_ttproducts_pdf.php');
			$pdf = GeneralUtility::makeInstance('tx_ttproducts_pdf');
			$pdf->init($cObj, 'Arial', '', 10);
			$pdf->setHeader($header);
			$pdf->setFooter($footer);
			$pdf->AddPage();
			$pdf->setBody($body);
			$pdf->Body();

			$pdf->Output($absFileName, 'F');
			$result = $absFileName;
		}
		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_pdf_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_pdf_view.php']);
}



