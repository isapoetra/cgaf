<?php
defined('CGAF') or die('Restricted access');;
return array(
	//Application to ignore while installation
		'appignoreinstall' => array(),
		'installed' => true,
		'DEBUGMODE' => true,
		'disableacl' => true,
		'disableCache' => false,
		'applicationPath' => realpath(dirname(__FILE__) . 'Applications'),
		'errors' => array(
				'debug' => array(
						'error_log' => null,
						'log_errors' => false,
						'display_error' => true,
						'error_reporting' => E_ALL,  //E_ERROR | E_WARNING | E_PARSE
				),
				'error_log' => null,
			// default internal storage
				'display_error' => false,
				'error_reporting' => E_ERROR | E_WARNING | E_PARSE
		),
		'locale' => array('debug' => false),
		'cgaf' => array(
				'vendorpath' => realpath(dirname(__FILE__) . 'vendor/'),
				'shared.path' => realpath(dirname(__FILE__) . DS . 'shared/'),
				'libspath' => realpath(dirname(__FILE__) . DS . 'Libs/'),
				'description' => 'Cipta Graha Application Framework v.' . CGAF_VERSION,
				'tags' => 'CGAF Framework PHP Application'
		),
	//change with your appid leave blank for use desktop
	//'defaultAppId'=> 'D85217B3-B696-6FFD-E0DC-453FBEB4AAF5',
		'db' => array(
				'type' => 'mysql',
				'host' => 'localhost',
				'table_prefix' => '',
				'username' => 'root',
				'password' => '', // replace with your password
				'database' => 'cgaf',
				'debug' => false
		),
		'mail' => array(
				'engine' => 'smtp',
				'sender' => 'admin@localhost',
				'smtp' => array('params' => array(
							'host' => 'localhost',
							'port' => 25
					))
		),
		'debug' => array(
				'allowedhost' => null,
			//comma separated host
				'locale' => false
		),
		'service' => array(
				'facebook' => array(
						'AppId' => '119265961478678',
						'secret' => 'c27440d5d985e668f5976ea0726e0e4d'
				),
				'google' => array('jsapi' => array('key' => 'ABQIAAAAcEI6O-fKaCIqWZMVEiE-RxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQQEp-XkWc_k56sIy5N5cmn_TkK4w'))
		),
		'Session' => array(
				'configs' => array(
						'use_cookies' => true,
						'auto_start' => false,
						'gc_maxlifetime' => 15 * 60
				),
			//auto logout after n second without activity.
				'Handler' => 'File',
				'cookie' => Array(),
				'security' => array(
						'fix_browser' => true,
						'fix_address' => true
				)
		),
);
?>
