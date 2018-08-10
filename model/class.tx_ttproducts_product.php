<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the product
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_product extends tx_ttproducts_article_base {
	public $marker = 'PRODUCT';
	public $type = 'product';
	public $piVar = 'product';
	public $articleArray = array();
	protected $tableAlias = 'product';


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($cObj, $functablename='tt_products')  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$tableConfig = array();
		$tableConfig['orderBy'] = $cnf->conf['orderBy'];

		if (!$tableConfig['orderBy'])	{
			 $tableConfig['orderBy'] = $this->getOrderBy();
		}

		$tableObj = $this->getTableObj();
		$tableObj->setConfig($tableConfig);
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));

// 		$requiredFields = 'uid,pid,category,price,price2,directcost,tax';
// 		$tableconf = $cnf->getTableConf($functablename);
// 		if ($tableconf['requiredFields'])	{
// 			$tmp = $tableconf['requiredFields'];
// 			$requiredFields = ($tmp ? $tmp : $requiredFields);
// 		}

		$this->relatedArray['accessories'] = array();
		$this->relatedArray['articles'] = array();
		$this->relatedArray['products'] = array();
	} // init


	public function fixTableConf (&$tableConf) {
		if (t3lib_extMgm::isLoaded('static_info_tables_taxes')) {
			$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables_taxes');

			if (is_array($eInfo)) {
				$sittVersion = $eInfo['version'];
				if (version_compare($sittVersion, '0.3.0', '>=')) {
					$tableConf['requiredFields'] = str_replace(',tax,', ',tax_id,', $tableConf['requiredFields']);
				}
			}
		}
	}


	public function &getArticleRows ($uid, $whereArticle='')	{
		$rowArray = $this->articleArray[$uid];

		if (!$rowArray && $uid || $whereArticle!='') {
			$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
			$articleObj = $tablesObj->get('tt_products_articles');
			$rowArray = $articleObj->getWhereArray($uid, $whereArticle);
			if (!$whereArticle)	{
				$this->articleArray[$uid] = $rowArray;
			}
		}
		return $rowArray;
	}


	public function fillVariantsFromArticles (&$row)	{

		$articleRowArray = $this->getArticleRows($row['uid']);
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$articleObj = $tablesObj->get('tt_products_articles');

		if (count($articleRowArray))	{
			// $articleObj->sortArticleRowsByUidArray($row['uid'],$articleRowArray);
			$variantRow = $this->variant->getVariantValuesByArticle($articleRowArray, $row, TRUE);
			$selectableFieldArray = $this->variant->getSelectableFieldArray();

			foreach ($selectableFieldArray as $field)	{
				if ($row[$field] == '')	{
					$row[$field] = $variantRow[$field];
				}
			}
		}
	}


	public function getArticleRowsFromVariant ($row, $theCode, $variant) {

		$articleRowArray = $this->getArticleRows(intval($row['uid']));
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$articleObj = $tablesObj->get('tt_products_articles');
	//	$articleRowArray = $articleObj->sortArticleRowsByUidArray($row['uid'],$articleRowArray);

		$rc = $this->variant->filterArticleRowsByVariant($row, $variant, $articleRowArray, TRUE);
		return $rc;
	}


	public function getMatchingArticleRows ($productRow, $articleRows) {

		$fieldArray = array();

		foreach ($this->variant->conf as $k => $field)	{
			if ($productRow[$field] && $field != $this->variant->additionalField)	{
				$fieldArray[$field] = t3lib_div::trimExplode(';', $productRow[$field]);
			}
		}
		$articleRow = array();

		if (count($fieldArray))	{

			$bFitArticleRowArray = array();

			foreach ($articleRows as $k => $row)	{
				$bFits = TRUE;
				foreach ($fieldArray as $field => $valueArray)	{
					$rowFieldArray = t3lib_div::trimExplode(';',$row[$field]);
					$intersectArray = array_intersect($valueArray, $rowFieldArray);

					if ($row[$field] && !count($intersectArray) && $field != 'additional')	{
						$bFits = FALSE;
						break;
					}
				}

				if ($bFits)	{
					$bFitArticleRowArray[] = $row;
				}
			}

			$articleCount = count($bFitArticleRowArray);
			$articleRow = $bFitArticleRowArray[0];

			if ($articleCount > 1)	{
				// many articles fit here. So lets generated a merged article.
				$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
				$articleObj = $tablesObj->get('tt_products_articles');
				for ($i=1; $i < $articleCount; ++$i)	{
					$articleObj->mergeAttributeFields($articleRow, $bFitArticleRowArray[$i], FALSE, TRUE, TRUE);
				}

				if (isset($articleRow['ext']))	{
					unset($articleRow['ext']);
				}
			}
		}

		return $articleRow;
	}


	public function getArticleRow ($row, $theCode, $bUsePreset=TRUE) {
		global $TYPO3_DB;

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$fieldArray = $this->variant->getSelectableFieldArray();
		$articleNo = FALSE;
		$regexpDelimiter = tx_ttproducts_model_control::determineRegExpDelimiter(';');

		if ($bUsePreset)	{
			$presetVarianArray = tx_ttproducts_control_product::getPresetVariantArray($row['uid']);

			if (!count($presetVarianArray))	{
				$articleNo = tx_ttproducts_control_product::getActiveArticleNo();
			}
		} else {
			$presetVarianArray = array();
		}

		if ($articleNo === FALSE)	{
			if (empty($presetVariantArray)) {
				$currentRow = $this->variant->getVariantRow($row);
			} else {
				$currentRow = $this->variant->getVariantRow($row, $presetVariantArray);
			}
		} else {
			$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
			$articleObj = $tablesObj->get('tt_products_articles');

			$articleRow = $articleObj->get($articleNo);
			$variantRow = $this->variant->getVariantValuesByArticle(array($articleRow), $row, TRUE);
			$currentRow = array_merge($row, $variantRow);
		}

		$whereArray = array();
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$articleObj = $tablesObj->get('tt_products_articles');

		foreach ($fieldArray as $k => $field)	{
			$whereClause = $field.'=\''.$currentRow[$field].'\'';

			$value = trim($currentRow[$field]);
			// $value = $TYPO3_DB->fullQuoteStr($value, $articleObj->getTablename());
			$regexpValue = $TYPO3_DB->quoteStr(quotemeta($value), $articleObj->getTablename());
			if ($value!='') {
				$whereClause =
					$field . ' REGEXP \'^[[:blank:]]*(' . $regexpValue . ')[[:blank:]]*$\'' .
					' OR ' . $field . ' REGEXP \'^[[:blank:]]*(' . $regexpValue . ')[[:blank:]]*[' . $regexpDelimiter . ']\'' .
					' OR ' . $field . ' REGEXP \'[' . $regexpDelimiter . '][[:blank:]]*(' . $regexpValue . ')[[:blank:]]*$\'';
				$whereArray[] = $whereClause;
			} else if ($this->conf['useArticles'] == 1) {
				$whereArray[] = $whereClause;
			}
		}


		if (count($whereArray))	{
			$where = '(' . implode (($this->conf['useArticles'] == '3' ? ' OR ' : ' AND '), $whereArray) . ')';
		} else {
			$where = '';
		}

		$articleRows = $this->getArticleRows(intval($row['uid']), $where);

		if (is_array($articleRows) && count($articleRows))	{

			$articleRow = $this->getMatchingArticleRows($currentRow, $articleRows);
			$articleConf = $cnf->getTableConf('tt_products_articles', $theCode);

			if (
				$theCode &&
				isset($articleConf['fieldIndex.']) && is_array($articleConf['fieldIndex.']) &&
				isset($articleConf['fieldIndex.']['image.']) && is_array($articleConf['fieldIndex.']['image.'])
			)	{
				$prodImageArray = t3lib_div::trimExplode(',',$row['image']);
				$artImageArray = t3lib_div::trimExplode(',',$articleRow['image']);
				$tmpDestArray = $prodImageArray;
				foreach ($articleConf['fieldIndex.']['image.'] as $kImage => $vImage)	{
					$tmpDestArray[$vImage-1] = $artImageArray[$kImage-1];
				}
				$articleRow['image'] = implode (',', $tmpDestArray);
			}
		}

		return $articleRow;
	}


	public function getRowFromExt ($funcTablename, $row, $useArticles)	{
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$priceRow = $row;

		if (
			in_array($useArticles, array(1,3)) &&
			$funcTablename == 'tt_products' &&
			isset($row['ext']['tt_products_articles']) &&
			is_array($row['ext']['tt_products_articles'])
		) {
			$articleObj = $tablesObj->get('tt_products_articles');
			reset($row['ext']['tt_products_articles']);

			$articleInfo = current($row['ext']['tt_products_articles']);
			foreach ($row['ext']['tt_products_articles'] as $extRow)	{

				$articleUid = $extRow['uid'];

				if (isset($articleUid))	{
					$articleRow = $articleObj->get($articleUid);
					$articleObj->mergeAttributeFields($priceRow, $articleRow, FALSE,TRUE);
				}

				if ($articleRow)	{
					$priceRow['weight'] = (round($articleRow['weight'], 16) ? $articleRow['weight'] : $row['weight']);
					$priceRow['inStock'] = $articleRow['inStock'];
				}
			}
		}
		return $priceRow;
	}


	public function getArticleRowFromExt ($row)	{
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');

		$rc = FALSE;
		$extArray = $row['ext'];

		if (isset($extArray) && is_array($extArray) && is_array($extArray['tt_products_articles']) && is_array($extArray['tt_products_articles']['0']))	{
			$articleUid = $extArray['tt_products_articles']['0']['uid'];
			$articleTable = $tablesObj->get('tt_products_articles', FALSE);
			$rc = $articleTable->get($articleUid);
		}
		return $rc;
	}


	public function getRelatedArrays (&$allowedRelatedTypeArray, &$mmTable) {
		$allowedRelatedTypeArray = array('accessories', 'articles', 'products');
		$mmTable = array(
			'accessories' => array('table' =>  'tt_products_accessory_products_products_mm'),
			'products' => array('table' =>  'tt_products_related_products_products_mm')
		);
	}


	// returns the Path of all categories above, separated by '/'
	public function getPath ($uid) {
		$rc = '';

		return $rc;
	}


	/**
	 * Reduces the instock value of the orderRecord with the sold items and returns the result
	 *
	 */
	public function &reduceInStockItems (&$itemArray, $useArticles)	{
		global $TYPO3_DB, $TCA;
		$instockTableArray = array();
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');

		$instockField = $cnf->getTableDesc($this->getTableObj()->name, 'inStock');
		$instockField = ($instockField ? $instockField : 'inStock');
		if ($this->getTableObj()->name == 'tt_products' || is_array(($TCA[$this->getTableObj()->name]['columns']['inStock'])) )	{
			// Reduce inStock
			if ($useArticles == 1) {
				// loop over all items in the basket indexed by a sorting text
				foreach ($itemArray as $sort=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {
						$row = $this->getArticleRow($actItem['rec'], $theCode);
						if ($row)	{
							$tt_products_articles = $tablesObj->get('tt_products_articles');
							$tt_products_articles->reduceInStock($row['uid'], $actItem['count']);
							$instockTableArray['tt_products_articles'][$row['uid'].','.$row['itemnumber'].','.$row['title']] = intval($row[$instockField] - $actItem['count']);
						}
					}
				}
			}
			// loop over all items in the basket indexed by a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = $actItem['rec'];
					if (!$this->hasAdditional($row,'alwaysInStock')) {
						$this->reduceInStock($row['uid'], $actItem['count']);
						$instockTableArray['tt_products'][$row['uid'].','.$row['itemnumber'].','.$row['title']] = intval($row[$instockField] - $actItem['count']);
					}
				}
			}
		}
		return $instockTableArray;
	}


	/**
	 * Returns TRUE if the item has the $check value checked
	 *
	 */
	public function hasAdditional (&$row, $check)  {
		$hasAdditional = FALSE;
		if (isset($row['additional'])) {
			$additional = t3lib_div::xml2array($row['additional']);
			$hasAdditional = tx_div2007_ff::get($additional, $check);
		}

		return $hasAdditional;
	}


	public function addWhereCat ($catObject, $theCode, $cat, $categoryAnd, $pid_list, $bLeadingOperator = TRUE)	{
		$bOpenBracket = FALSE;
		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
				$hookObj = t3lib_div::makeInstance($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {

					$whereNew = $hookObj->addWhereCat($this, $catObject, $cat, $where, $operator, $pid_list, $catObject->getDepth($theCode), $categoryAnd);

					if ($bLeadingOperator)	{
						$operator = ($operator ? $operator : 'OR');
						$where .= ($whereNew ? ' '.$operator.' '.$whereNew : '');
					} else {
						$where .= $whereNew;
					}
				}
			}
		} else {
			$catArray = array();
			$categoryAndArray = array();

			if($cat || $cat == '0') {
				$catArray = t3lib_div::intExplode(',', $cat);
			}
			if($categoryAnd != '') {
				$categoryAndArray = t3lib_div::intExplode(',', $categoryAnd);
			}
			$newcatArray = array_merge($categoryAndArray, $catArray);
			$newcatArray = array_unique($newcatArray);

			if (count($newcatArray)) {
				$newcats = implode(',', $newcatArray);
				$where = 'category IN (' . $newcats . ')';

				if ($bLeadingOperator)	{
					$where = ' AND ( ' . $where . ')';
				}
			}
		}

		return $where;
	}


	public function addConfCat ($catObject, &$selectConf, $aliasArray)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
				$hookObj= t3lib_div::makeInstance($classRef);
				if (method_exists($hookObj, 'addConfCatProduct')) {
					$newTablenames = $hookObj->addConfCatProduct($this, $catObject, $selectConf, $aliasArray);
					if ($newTablenames != '')	{
						$tableNameArray[] = $newTablenames;
					}
				}
			}
		}
		return implode(',', $tableNameArray);
	}


	public function addselectConfCat ($catObject, $cat, &$selectConf)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
				$hookObj= t3lib_div::makeInstance($classRef);
				if (method_exists($hookObj, 'addselectConfCat')) {
					$newTablenames = $hookObj->addselectConfCat($this, $catObject, $cat, $selectConf,$catObject->getDepth());
					if ($newTablenames != '')	{
						$tableNameArray[] = $newTablenames;
					}
				}
			}
		}
		return implode(',', $tableNameArray);
	}


	public function getPageUidsCat ($cat)	{
		$uidArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
				$hookObj= t3lib_div::makeInstance($classRef);
				if (method_exists($hookObj, 'getPageUidsCat')) {
					$hookObj->getPageUidsCat($this, $cat, $uidArray);
				}
			}
		}
		$uidArray = array_unique($uidArray);
		return (implode(',',$uidArray));
	}


	public function getProductField (&$row, $field)	{
		return $row[$field];
	}


	public function getRequiredFields ($theCode='')	{
		$tableConf = $this->getTableConf($theCode);
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');

		if ($tableConf['requiredFields']!='')	{
			$requiredFields = $tableConf['requiredFields'];
		} else {
			$requiredFields = 'uid,pid,category,price,price2,discount,discount_disable,directcost,tax';
		}
		$instockField = $cnf->getTableDesc($functablename,'inStock');
		if ($instockField && !$this->conf['alwaysInStock'])	{
			$requiredFields = $requiredFields.','.$instockField;
		}
		$rc = $requiredFields;
		return $rc;
	}


	public function getTotalDiscount (&$row, $pid = 0) {

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');

		if (
			$this->getFuncTablename() == 'tt_products' &&
			in_array(
				$this->conf['discountFieldMode'],
				array('1', '2')
			)
		) {
			$categoryfunctablename = 'tt_products_cat';
			$categoryTable = $tablesObj->get($categoryfunctablename, FALSE);
			$discount = 0;

			switch ($this->conf['discountFieldMode']) {
				case '1':
					$catArray = $categoryTable->getCategoryArray($row['uid'], 'sorting');
					$discount = $categoryTable->getMaxDiscount(
						$row['discount'],
						$row['discount_disable'],
						$catArray,
						$pid
					);
					break;
				case '2':
					$discount = $categoryTable->getFirstDiscount(
						$row['discount'],
						$row['discount_disable'],
						$row['category'],
						$pid
					);
					break;
			}

			$discountField = $this->getTotalDiscountField();
			$row[$discountField] = $discount;
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']);
}


?>
