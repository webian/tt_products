<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_products".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop System',
	'description' => 'New versions at ttproducts.de. Documented in E-Book "Der TYPO3-Webshop" - Shop with listing in multiple languages, with order tracking, photo gallery, DAM, product variants, credit card payment and bank accounts, bill, creditpoint, voucher system and gift certificates. Latest updates at ttproducts.de.',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,table,div2007',
	'conflicts' => 'su_products,zk_products,mkl_products,ast_rteproducts,onet_ttproducts_rte,shopsort,c3bi_cookie_at_login',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,uploads/tx_ttproducts/rte,fileadmin/data/bill,fileadmin/data/delivery,fileadmin/img',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.8.22',
	'constraints' => array(
		'depends' => array(
			'div2007' => '1.7.11-0.0.0',
            'migration_core' => '0.0.0-0.99.99',
			'php' => '5.2.0-7.99.99',
			'table' => '0.3.0-0.0.0',
			'tsparser' => '0.2.5-0.0.0',
			'typo3' => '4.5.0-8.99.99',
		),
		'conflicts' => array(
			'mkl_products' => '',
			'su_products' => '',
			'zk_products' => '',
			'ast_rteproducts' => '',
			'onet_ttproducts_rte' => '',
			'shopsort' => '',
			'c3bi_cookie_at_login' => '',
		),
		'suggests' => array(
			'func_wizards' => '',
			'addons_em' => '0.2.1-0.0.0',
		),
	),
    'autoload' => array(
        'psr-4' => array('JambageCom\\TtProducts\\' => 'Classes'),
        'classmap' => array(
            'api',
            'cache',
            'Classes',
            'control',
            'eid',
            'hooks',
            'lib',
            'marker',
            'model',
            'modfunc1',
            'modfunc2',
            'pi1',
            'pi_int',
            'pi_search',
            'view',
            'widgets',
        )
    ),
);

