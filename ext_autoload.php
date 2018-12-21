<?php

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$extensionPath = call_user_func($emClass . '::extPath', 'tt_products');

return array(
	'tx_ttproducts_wizicon' => $extensionPath . 'class.tx_ttproducts_wizicon.php',
	'tx_ttproducts_api' => $extensionPath . 'api/class.tx_ttproducts_api.php',
	'tx_ttproducts_ts' => $extensionPath . 'api/class.tx_ttproducts_ts.php',
	'tx_ttproducts_cache' => $extensionPath . 'cache/class.tx_ttproducts_cache.php',
	'tx_ttproducts_activity_base' => $extensionPath . 'control/class.tx_ttproducts_activity_base.php',
	'tx_ttproducts_activity_finalize' => $extensionPath . 'control/class.tx_ttproducts_activity_finalize.php',
	'tx_ttproducts_control' => $extensionPath . 'control/class.tx_ttproducts_control.php',
	'tx_ttproducts_control_address' => $extensionPath . 'control/class.tx_ttproducts_control_address.php',
	'tx_ttproducts_control_basket' => $extensionPath . 'control/class.tx_ttproducts_control_basket.php',
	'tx_ttproducts_control_basketquantity' => $extensionPath . 'control/class.tx_ttproducts_control_basketquantity.php',
	'tx_ttproducts_control_creator' => $extensionPath . 'control/class.tx_ttproducts_control_creator.php',
	'tx_ttproducts_control_memo' => $extensionPath . 'control/class.tx_ttproducts_control_memo.php',
	'tx_ttproducts_control_product' => $extensionPath . 'control/class.tx_ttproducts_control_product.php',
	'tx_ttproducts_control_search' => $extensionPath . 'control/class.tx_ttproducts_control_search.php',
	'tx_ttproducts_control_session' => $extensionPath . 'control/class.tx_ttproducts_control_session.php',
	'tx_ttproducts_control_single' => $extensionPath . 'control/class.tx_ttproducts_control_single.php',
	'tx_ttproducts_control_user_int' => $extensionPath . 'control/class.tx_ttproducts_control_user_int.php',
	'tx_ttproducts_javascript' => $extensionPath . 'control/class.tx_ttproducts_javascript.php',
	'tx_ttproducts_main' => $extensionPath . 'control/class.tx_ttproducts_main.php',
	'tx_ttproducts_ajax' => $extensionPath . 'eid/class.tx_ttproducts_ajax.php',
	'tx_ttproducts_db' => $extensionPath . 'eid/class.tx_ttproducts_db.php',
	'tx_ttproducts_hooks_be' => $extensionPath . 'hooks/class.tx_ttproducts_hooks_be.php',
	'tx_ttproducts_hooks_cms' => $extensionPath . 'hooks/class.tx_ttproducts_hooks_cms.php',
	'tx_ttproducts_hooks_fe' => $extensionPath . 'hooks/class.tx_ttproducts_hooks_fe.php',
	'tx_ttproducts_hooks_pool' => $extensionPath . 'hooks/class.tx_ttproducts_hooks_pool.php',
	'tx_ttproducts_hooks_transactor' => $extensionPath . 'hooks/class.tx_ttproducts_hooks_transactor.php',
	'tx_ttproducts_match_condition' => $extensionPath . 'hooks/class.tx_ttproducts_match_condition.php',
	'tx_ttproducts_billdelivery' => $extensionPath . 'lib/class.tx_ttproducts_billdelivery.php',
	'tx_ttproducts_config' => $extensionPath . 'lib/class.tx_ttproducts_config.php',
	'tx_ttproducts_creditpoints_div' => $extensionPath . 'lib/class.tx_ttproducts_creditpoints_div.php',
	'tx_ttproducts_css' => $extensionPath . 'lib/class.tx_ttproducts_css.php',
	'tx_ttproducts_csv' => $extensionPath . 'lib/class.tx_ttproducts_csv.php',
	'tx_ttproducts_discountprice' => $extensionPath . 'lib/class.tx_ttproducts_discountprice.php',
	'tx_ttproducts_email_div' => $extensionPath . 'lib/class.tx_ttproducts_email_div.php',
	'tx_ttproducts_form_div' => $extensionPath . 'lib/class.tx_ttproducts_form_div.php',
	'tx_ttproducts_gifts_div' => $extensionPath . 'lib/class.tx_ttproducts_gifts_div.php',
	'tx_ttproducts_integrate' => $extensionPath . 'lib/class.tx_ttproducts_integrate.php',
	'tx_ttproducts_paymentshipping' => $extensionPath . 'lib/class.tx_ttproducts_paymentshipping.php',
	'tx_ttproducts_pricecalc' => $extensionPath . 'lib/class.tx_ttproducts_pricecalc.php',
	'tx_ttproducts_pricecalc_base' => $extensionPath . 'lib/class.tx_ttproducts_pricecalc_base.php',
	'tx_ttproducts_pricetablescalc' => $extensionPath . 'lib/class.tx_ttproducts_pricetablescalc.php',
	'tx_ttproducts_sql' => $extensionPath . 'lib/class.tx_ttproducts_sql.php',
	'tx_ttproducts_tables' => $extensionPath . 'lib/class.tx_ttproducts_tables.php',
	'tx_ttproducts_template' => $extensionPath . 'lib/class.tx_ttproducts_template.php',
	'tx_ttproducts_tracking' => $extensionPath . 'lib/class.tx_ttproducts_tracking.php',
	'tx_ttproducts_javascript_marker' => $extensionPath . 'marker/class.tx_ttproducts_javascript_marker.php',
	'tx_ttproducts_marker' => $extensionPath . 'marker/class.tx_ttproducts_marker.php',
	'tx_ttproducts_subpartmarker' => $extensionPath . 'marker/class.tx_ttproducts_subpartmarker.php',
	'tx_ttproducts_account' => $extensionPath . 'model/class.tx_ttproducts_account.php',
	'tx_ttproducts_address' => $extensionPath . 'model/class.tx_ttproducts_address.php',
	'tx_ttproducts_article' => $extensionPath . 'model/class.tx_ttproducts_article.php',
	'tx_ttproducts_article_base' => $extensionPath . 'model/class.tx_ttproducts_article_base.php',
	'tx_ttproducts_bank_de' => $extensionPath . 'model/class.tx_ttproducts_bank_de.php',
	'tx_ttproducts_basket' => $extensionPath . 'model/class.tx_ttproducts_basket.php',
	'tx_ttproducts_basket_calculate' => $extensionPath . 'model/class.tx_ttproducts_basket_calculate.php',
	'tx_ttproducts_basketitem' => $extensionPath . 'model/class.tx_ttproducts_basketitem.php',
	'tx_ttproducts_card' => $extensionPath . 'model/class.tx_ttproducts_card.php',
	'tx_ttproducts_category' => $extensionPath . 'model/class.tx_ttproducts_category.php',
	'tx_ttproducts_category_base' => $extensionPath . 'model/class.tx_ttproducts_category_base.php',
	'tx_ttproducts_content' => $extensionPath . 'model/class.tx_ttproducts_content.php',
	'tx_ttproducts_country' => $extensionPath . 'model/class.tx_ttproducts_country.php',
	'tx_ttproducts_dam' => $extensionPath . 'model/class.tx_ttproducts_dam.php',
	'tx_ttproducts_damcategory' => $extensionPath . 'model/class.tx_ttproducts_damcategory.php',
	'tx_ttproducts_email' => $extensionPath . 'model/class.tx_ttproducts_email.php',
	'tx_ttproducts_graduated_price' => $extensionPath . 'model/class.tx_ttproducts_graduated_price.php',
	'tx_ttproducts_language' => $extensionPath . 'model/class.tx_ttproducts_language.php',
	'tx_ttproducts_mm_table' => $extensionPath . 'model/class.tx_ttproducts_mm_table.php',
	'tx_ttproducts_model_activity' => $extensionPath . 'model/class.tx_ttproducts_model_activity.php',
	'tx_ttproducts_model_control' => $extensionPath . 'model/class.tx_ttproducts_model_control.php',
	'tx_ttproducts_model_creator' => $extensionPath . 'model/class.tx_ttproducts_model_creator.php',
	'tx_ttproducts_model_error' => $extensionPath . 'model/class.tx_ttproducts_model_error.php',
	'tx_ttproducts_order' => $extensionPath . 'model/class.tx_ttproducts_order.php',
	'tx_ttproducts_orderaddress' => $extensionPath . 'model/class.tx_ttproducts_orderaddress.php',
	'tx_ttproducts_page' => $extensionPath . 'model/class.tx_ttproducts_page.php',
	'tx_ttproducts_pdf' => $extensionPath . 'model/class.tx_ttproducts_pdf.php',
	'tx_ttproducts_pid_list' => $extensionPath . 'model/class.tx_ttproducts_pid_list.php',
	'tx_ttproducts_product' => $extensionPath . 'model/class.tx_ttproducts_product.php',
	'tx_ttproducts_static_info' => $extensionPath . 'model/class.tx_ttproducts_static_info.php',
	'tx_ttproducts_static_tax' => $extensionPath . 'model/class.tx_ttproducts_static_tax.php',
	'tx_ttproducts_table_base' => $extensionPath . 'model/class.tx_ttproducts_table_base.php',
	'tx_ttproducts_table_label' => $extensionPath . 'model/class.tx_ttproducts_table_label.php',
	'tx_ttproducts_text' => $extensionPath . 'model/class.tx_ttproducts_text.php',
	'tx_ttproducts_variant' => $extensionPath . 'model/class.tx_ttproducts_variant.php',
	'tx_ttproducts_variant_dummy' => $extensionPath . 'model/class.tx_ttproducts_variant_dummy.php',
	'tx_ttproducts_voucher' => $extensionPath . 'model/class.tx_ttproducts_voucher.php',
	'tx_ttproducts_variant_int' => $extensionPath . 'model/interface.tx_ttproducts_variant_int.php',
	'tx_ttproducts_field_base' => $extensionPath . 'model/field/class.tx_ttproducts_field_base.php',
	'tx_ttproducts_field_creditpoints' => $extensionPath . 'model/field/class.tx_ttproducts_field_creditpoints.php',
	'tx_ttproducts_field_datafield' => $extensionPath . 'model/field/class.tx_ttproducts_field_datafield.php',
	'tx_ttproducts_field_datetime' => $extensionPath . 'model/field/class.tx_ttproducts_field_datetime.php',
	'tx_ttproducts_field_foreign_table' => $extensionPath . 'model/field/class.tx_ttproducts_field_foreign_table.php',
	'tx_ttproducts_field_graduated_price' => $extensionPath . 'model/field/class.tx_ttproducts_field_graduated_price.php',
	'tx_ttproducts_field_image' => $extensionPath . 'model/field/class.tx_ttproducts_field_image.php',
	'tx_ttproducts_field_instock' => $extensionPath . 'model/field/class.tx_ttproducts_field_instock.php',
	'tx_ttproducts_field_media' => $extensionPath . 'model/field/class.tx_ttproducts_field_media.php',
	'tx_ttproducts_field_note' => $extensionPath . 'model/field/class.tx_ttproducts_field_note.php',
	'tx_ttproducts_field_price' => $extensionPath . 'model/field/class.tx_ttproducts_field_price.php',
	'tx_ttproducts_field_tax' => $extensionPath . 'model/field/class.tx_ttproducts_field_tax.php',
	'tx_ttproducts_field_text' => $extensionPath . 'model/field/class.tx_ttproducts_field_text.php',
	'tx_ttproducts_field_int' => $extensionPath . 'model/field/interface.tx_ttproducts_field_int.php',
	'tx_ttproducts_modfunc1' => $extensionPath . 'modfunc1/class.tx_ttproducts_modfunc1.php',
	'tx_ttproducts_modfunc2' => $extensionPath . 'modfunc2/class.tx_ttproducts_modfunc2.php',
	'tx_ttproducts_pi1' => $extensionPath . 'pi1/class.tx_ttproducts_pi1.php',
	'tx_ttproducts_pi1_base' => $extensionPath . 'pi1/class.tx_ttproducts_pi1_base.php',
	'tx_ttproducts_pi_int' => $extensionPath . 'pi_int/class.tx_ttproducts_pi_int.php',
	'tx_ttproducts_pi_int_base' => $extensionPath . 'pi_int/class.tx_ttproducts_pi_int_base.php',
	'tx_ttproducts_pi_search' => $extensionPath . 'pi_search/class.tx_ttproducts_pi_search.php',
	'tx_ttproducts_pi_search_base' => $extensionPath . 'pi_search/class.tx_ttproducts_pi_search_base.php',
	'tx_ttproducts_account_view' => $extensionPath . 'view/class.tx_ttproducts_account_view.php',
	'tx_ttproducts_address_view' => $extensionPath . 'view/class.tx_ttproducts_address_view.php',
	'tx_ttproducts_article_base_view' => $extensionPath . 'view/class.tx_ttproducts_article_base_view.php',
	'tx_ttproducts_article_view' => $extensionPath . 'view/class.tx_ttproducts_article_view.php',
	'tx_ttproducts_basket_view' => $extensionPath . 'view/class.tx_ttproducts_basket_view.php',
	'tx_ttproducts_basketitem_view' => $extensionPath . 'view/class.tx_ttproducts_basketitem_view.php',
	'tx_ttproducts_card_view' => $extensionPath . 'view/class.tx_ttproducts_card_view.php',
	'tx_ttproducts_cat_view' => $extensionPath . 'view/class.tx_ttproducts_cat_view.php',
	'tx_ttproducts_category_base_view' => $extensionPath . 'view/class.tx_ttproducts_category_base_view.php',
	'tx_ttproducts_category_view' => $extensionPath . 'view/class.tx_ttproducts_category_view.php',
	'tx_ttproducts_catlist_view' => $extensionPath . 'view/class.tx_ttproducts_catlist_view.php',
	'tx_ttproducts_catlist_view_base' => $extensionPath . 'view/class.tx_ttproducts_catlist_view_base.php',
	'tx_ttproducts_control_view' => $extensionPath . 'view/class.tx_ttproducts_control_view.php',
	'tx_ttproducts_country_view' => $extensionPath . 'view/class.tx_ttproducts_country_view.php',
	'tx_ttproducts_currency_view' => $extensionPath . 'view/class.tx_ttproducts_currency_view.php',
	'tx_ttproducts_dam_view' => $extensionPath . 'view/class.tx_ttproducts_dam_view.php',
	'tx_ttproducts_damcategory_view' => $extensionPath . 'view/class.tx_ttproducts_damcategory_view.php',
	'tx_ttproducts_graduated_price_view' => $extensionPath . 'view/class.tx_ttproducts_graduated_price_view.php',
	'tx_ttproducts_info_view' => $extensionPath . 'view/class.tx_ttproducts_info_view.php',
	'tx_ttproducts_list_view' => $extensionPath . 'view/class.tx_ttproducts_list_view.php',
	'tx_ttproducts_memo_view' => $extensionPath . 'view/class.tx_ttproducts_memo_view.php',
	'tx_ttproducts_menucat_view' => $extensionPath . 'view/class.tx_ttproducts_menucat_view.php',
	'tx_ttproducts_order_view' => $extensionPath . 'view/class.tx_ttproducts_order_view.php',
	'tx_ttproducts_orderaddress_view' => $extensionPath . 'view/class.tx_ttproducts_orderaddress_view.php',
	'tx_ttproducts_page_view' => $extensionPath . 'view/class.tx_ttproducts_page_view.php',
	'tx_ttproducts_pdf_view' => $extensionPath . 'view/class.tx_ttproducts_pdf_view.php',
	'tx_ttproducts_product_view' => $extensionPath . 'view/class.tx_ttproducts_product_view.php',
	'tx_ttproducts_relatedlist_view' => $extensionPath . 'view/class.tx_ttproducts_relatedlist_view.php',
	'tx_ttproducts_search_view' => $extensionPath . 'view/class.tx_ttproducts_search_view.php',
	'tx_ttproducts_selectcat_view' => $extensionPath . 'view/class.tx_ttproducts_selectcat_view.php',
	'tx_ttproducts_single_view' => $extensionPath . 'view/class.tx_ttproducts_single_view.php',
	'tx_ttproducts_static_tax_view' => $extensionPath . 'view/class.tx_ttproducts_static_tax_view.php',
	'tx_ttproducts_table_base_view' => $extensionPath . 'view/class.tx_ttproducts_table_base_view.php',
	'tx_ttproducts_text_view' => $extensionPath . 'view/class.tx_ttproducts_text_view.php',
	'tx_ttproducts_url_view' => $extensionPath . 'view/class.tx_ttproducts_url_view.php',
	'tx_ttproducts_user_view' => $extensionPath . 'view/class.tx_ttproducts_user_view.php',
	'tx_ttproducts_variant_dummy_view' => $extensionPath . 'view/class.tx_ttproducts_variant_dummy_view.php',
	'tx_ttproducts_variant_view' => $extensionPath . 'view/class.tx_ttproducts_variant_view.php',
	'tx_ttproducts_voucher_view' => $extensionPath . 'view/class.tx_ttproducts_voucher_view.php',
	'tx_ttproducts_variant_view_int' => $extensionPath . 'view/interface.tx_ttproducts_variant_view_int.php',
	'tx_ttproducts_field_base_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_base_view.php',
	'tx_ttproducts_field_creditpoints_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_creditpoints_view.php',
	'tx_ttproducts_field_datafield_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_datafield_view.php',
	'tx_ttproducts_field_datetime_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_datetime_view.php',
	'tx_ttproducts_field_foreign_table_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_foreign_table_view.php',
	'tx_ttproducts_field_graduated_price_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_graduated_price_view.php',
	'tx_ttproducts_field_image_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_image_view.php',
	'tx_ttproducts_field_instock_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_instock_view.php',
	'tx_ttproducts_field_media_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_media_view.php',
	'tx_ttproducts_field_note_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_note_view.php',
	'tx_ttproducts_field_price_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_price_view.php',
	'tx_ttproducts_field_tax_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_tax_view.php',
	'tx_ttproducts_field_text_view' => $extensionPath . 'view/field/class.tx_ttproducts_field_text_view.php',
	'tx_ttproducts_field_view_int' => $extensionPath . 'view/field/interface.tx_ttproducts_field_view_int.php',
	'tx_ttproducts_latest' => $extensionPath . 'widgets/class.tx_ttproducts_latest.php',
);
