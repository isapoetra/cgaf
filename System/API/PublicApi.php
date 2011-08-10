<?php
namespace System\API;
use System\Configurations\Configuration;
use \AppManager;

abstract class PublicApi {
	private $_config;
	protected static $_appOwner;
	private static $_instances = array();

	function __construct($config = array()) {
		$this->_config = new Configuration($config, false);

	}

	abstract function init($service);

	public static function getInstance($name) {
		static $instance;
		if (isset(self::$_instances[$name])) {
			return self::$_instances[$name];
		}
		CGAF::Using("System.API." . strtolower($name));
		$cname = $name . 'Api';
		self::$_instances[$name] = new $cname(self::getAppOwner());
		return self::$_instances[$name];
	}

	public static function getAppOwner() {
		return self::$_appOwner ? self::$_appOwner : AppManager::getInstance();
	}

	public static function Initialize($appOwner) {
		self::$_appOwner = $appOwner;
		$share = $appOwner->getConfig('app.web.share');
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

	public static function render($return = false) {
		$share = self::getAppOwner()->getConfig('app.web.share');
		$retval = '';
		if ($share) {
			foreach ($share as $k => $v) {
				$instance = self::getInstance($k);
				foreach ($v as $kk => $vv) {
					$retval .= $instance->$kk();
				}
			}
		}
		return $retval;
	}
}
