<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger (franz@ttproducts.de)
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
 * AJAX control over select boxes for categories
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_selectcat_view extends tx_ttproducts_catlist_view_base {

	var $htmlTagMain = 'select';	// main HTML tag
	var $htmlTagElement = 'option';

	// returns the products list view
	public function &printView ($functablename, &$templateCode, $theCode, &$error_code, $templateArea = 'ITEM_CATEGORY_SELECT_TEMPLATE', $pageAsCategory, $templateSuffix = '') {
		global $TSFE, $TCA;
		$content='';
		$out='';
		$where='';

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
		$categoryTableView = $tablesObj->get($functablename,1);
		$categoryTable = $categoryTableView->getModelObj();

		$bSeparated = false;
		$method = 'clickShow';
		$t = array();
		$ctrlArray = array();

		parent::getPrintViewArrays(
			$functablename,
			$templateCode,
			$t,
			$htmlParts,
			$theCode,
			$error_code,
			$templateArea,
			$pageAsCategory,
			$templateSuffix,
			$currentCat,
			$categoryArray,
			$catArray,
			$isParentArray,
			$subCategoryMarkers,
			$ctrlArray
		);

		if (!count($error_code)) 	{
			$count = 0;
			$depth = 1;

			if($pos = strpos($t['listFrameWork'],'###CATEGORY_SINGLE_'))	{
				$bSeparated = true;
			}
			$contentId = '';

			$contentPos = strpos($this->pibase->cObj->currentRecord, 'tt_content');
			if ($contentPos !== FALSE) {
				$contentIdPos = strpos($this->pibase->cObj->currentRecord, ':');
				$contentId = substr($this->pibase->cObj->currentRecord, $contentIdPos + 1);
			}

			$menu = $this->conf['CSS.'][$functablename.'.']['menu'];
			$menu = ($menu ? $menu : $categoryTableView->getPivar() . '-' . $contentId . '-' . $depth);
			$fill = '';
			if ($method == 'clickShow')	{
				if ($bSeparated) {
					$fill = 'fillSelect(this,2,' . $contentId . ',1);';
				} else {
					$fill = 'fillSelect(this,0,' . $contentId . ',0);';
				}
			}

			$selectArray = array();
			if (is_array($this->conf['form.'][$theCode.'.']) && is_array($this->conf['form.'][$theCode.'.']['dataArray.']))	{
				foreach ($this->conf['form.'][$theCode.'.']['dataArray.'] as $k => $setting)	{
					if (is_array($setting))	{
						$selectArray[$k] = array();
						$type = $setting['type'];
						if ($type)	{
							$parts = t3lib_div::trimExplode('=', $type);
							if ($parts[1] == 'select')	{
								$selectArray[$k]['name'] = $parts[0];
							}
						}
						$label = $setting['label'];
						if ($label)	{
							$selectArray[$k]['label'] = $label;
						}
						$params = $setting['params'];
						if ($params)	{
							$selectArray[$k]['params'] = $params;
						}
					}
				}
			}

			$label = '';
			$name = 'tt_products[' . strtolower($theCode) . ']';

			reset($selectArray);
			$select = current($selectArray);
			if (is_array($select)) {
				if ($select['name']) {
					$name = $select['name'];
				}
				if ($select['label']) {
					$label = $select['label'] . ' ';
				}
				if ($select['params']) {
					$params = $select['params'];
				}
			}

			$selectedKey = '0';
			$valueArray = array();
			$valueArray['0'] = '';
			$selectedCat = $currentCat;

			if (is_array($catArray[$depth])) {
				foreach ($catArray[$depth] as $k => $actCategory) {
					if (!$categoryArray[$actCategory]['reference_category']) {
						$valueArray[$actCategory] = $categoryArray[$actCategory]['title'];
					} else
						{
					}
				}
			}

			$mainAttributeArray = array();
			$mainAttributeArray['id'] = $menu;
			if ($fill != '') {
				$mainAttributeArray['onchange'] = $fill;
			}

			$foreignRootLine = $categoryTable->getRootline(array('0'), $currentCat, 0);

			if (is_array($foreignRootLine)) {
				foreach ($foreignRootLine as $cat => $foreignRow) {
					if (
						isset($valueArray[$cat]) ||
						(
							isset($categoryArray[$cat]) &&
							$categoryArray[$cat]['reference_category'] > 0 &&
							isset($valueArray[$categoryArray[$cat]['reference_category']])
						)
					) {
						$mainAttributeArray['disabled'] = 'disabled';
						$selectedCat = $cat;
						if (!isset($valueArray[$cat])) {
							$selectedCat = $categoryArray[$cat]['reference_category'];
						}
						$mainAttributeArray['class'] .= (isset($mainAttributeArray['class']) ? ' ' : '') . 'sel-inactive';
						break;;
					}
				}
			}
			$paramArray = t3lib_div::get_tag_attributes($params);
			if (isset($paramArray) && is_array($paramArray)) {
				$mainAttributeArray = array_merge($mainAttributeArray, $paramArray);
			}

			if (!$valueArray[$selectedCat]) {
				$selectedCat = '0';
			}

			$selectOut = tx_ttproducts_form_div::createSelect(
				$langObj,
				$valueArray,
				$name,
				$selectedCat,
				$bSelectTags = TRUE,
				$bTranslateText = FALSE,
				array(),
				$this->htmlTagMain,
				$mainAttributeArray,
				$layout = '',
				$imageFileArray = '',
				$keyMarkerArray = ''
			);
			$out = $label . $selectOut;
			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$markerArray = $this->urlObj->addURLMarkers($this->conf['PIDlistDisplay'],$markerArray);
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray['###CATEGORY_SINGLE###'] = $out;

			$count = intval(substr_count($t['listFrameWork'], '###CATEGORY_SINGLE_') / 2);
			if ($pageAsCategory == 2)	{
				// $catid = 'pid';
				$parentFieldArray = array('pid');
			} else {
				// $catid = 'cat';
				$parentFieldArray = array('parent_category');
			}
			$piVar = $categoryTableView->piVar;

			if ($method == 'clickShow') {
				$javaScriptObj = t3lib_div::makeInstance('tx_ttproducts_javascript');
				$javaScriptObj->set(
					'selectcat',
					array($categoryArray),
					$this->pibase->cObj->currentRecord,
					1 + $count,
					'cat',
					$parentFieldArray,
					array($piVar),
					array(),
					'clickShow'
				);
			}

			if ($bSeparated) {
				for ($i = 2; $i <= 1 + $count; ++$i) {
					$menu = $piVar . '-' . $contentId . '-' . $i;
					$bShowSubcategories = ($i < 1+$count ? 1 : 0);
					$boxNumber = ($i < 1 + $count ? ($i + 1) : 0);
					$fill = ' onchange="fillSelect(this, ' . $boxNumber . ',' . $contentId . ',' . $bShowSubcategories . ');"';
					$tmp = '<' . $this->htmlTagMain . ' id="' . $menu . '"' . $fill . '>';
					$tmp .= '<option value="0"></option>';
					$tmp .= '</' . $this->htmlTagMain . '>';
					$subpartArray['###CATEGORY_SINGLE_' . $i . '###'] = $tmp;
				}

				// $subpartArray['###CATEGORY_SINGLE_BUTTON'] = '<input type="button" value="Laden" onclick="fillSelect(0, '.$boxNumber.','.$bShowSubcategories.');">';
			}

			$out =
				$this->pibase->cObj->substituteMarkerArrayCached(
					$t['listFrameWork'],
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray
				);
			$content = $out;
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_selectcat_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_selectcat_view.php']);
}


