<?php
namespace System\API;
use System\Configurations\Configuration;
use \AppManager;
use \CGAF;
abstract class PublicApi {
	protected $_config;
	protected static $_appOwner;
	private static $_instances = array();
	function __construct($config = array()) {
		$this->_config = new Configuration($config, false);
	}
	abstract function init($service);
	function setConfigs($configs) {
		$this->_config->setConfigs($configs);
	}
	public static function getAppOwner() {
		return self::$_appOwner ? self::$_appOwner : AppManager::getInstance();
	}
	private static function Initialize($appOwner = null) {
		static $initialized;
		if ($initialized)
			return;
		$initialized = true;
		self::$_appOwner = $appOwner ? $appOwner : AppManager::getInstance();

		$share = self::$_appOwner->getConfig('app.web.share');
		if ($share) {
			foreach ($share as $k => $v) {
				$instance = self::getInstance($k);
				foreach ($v as $kk => $vv) {
					$initName = 'init' . $kk;
					$instance->SetConfig($kk, $vv);
					$instance->init($kk);
					if (method_exists($instance, $initName)) {
						$instance->{$initName}($vv);
					}
				}
			}
		}
	}
	function setConfig($configName, $value) {
		$this->_config->setConfig($configName, $value);
	}
	protected function getConfig($configName, $default = null) {
		return $this->_config->getConfig($configName, $default);
	}

}
