<?php
namespace System\API;
use System\Exceptions\SystemException;
use System\Configurations\Configuration;
use \AppManager;
use \CGAF;
abstract class PublicApi {
	protected $_apijs = array();
	protected $_config;
	protected static $_appOwner;
	private static $_instances = array();
	function __construct($config = array()) {
		$this->_config = new Configuration($config, false);
	}
	public function initJS() {
	}
	public function init($service) {
		$service = strtolower($service);
		$this->initJS();
		$js = isset($this->_apijs[$service]) ? $this->_apijs[$service] : null;
		if ($js) {
			AppManager::getInstance()->addClientAsset($js);
		}
	}
	function setConfigs($configs) {
		$this->_config->setConfigs($configs);
	}
	public static function getAppOwner() {
		return self::$_appOwner ? self::$_appOwner : AppManager::getInstance();
	}
	public static function share($api, $method, $config = null) {
		$instance = self::getInstance($api);
		if (method_exists($instance, $method)) {
			$instance->init($method);
			return $instance->$method($config);
		}
		if (CGAF_DEBUG) {
			throw new SystemException('undefined method ' . $method . ' on class ' . get_class($instance));
		}
	}

    /**
     * @param $api
     * @param array $configs
     * @return PublicApi
     */
    public static function getInstance($api,$configs= array()) {
		$c = "\\System\\API\\" . $api;
		return new $c($configs);
	}
    public static function isOnlineContact($contact) {
        switch ($contact->api ) {
            case 'google':
            case 'yahoo':
                return true;
            case 'contacts':
                switch ($contact->type) {
                    case 'email':
                        return true;
                }
        }

        return false;
    }
	public static function Initialize($appOwner = null) {
		static $initialized;
		if ($initialized)
			return;
		$initialized = true;
		self::$_appOwner = $appOwner ? $appOwner : AppManager::getInstance();
		$share = self::$_appOwner->getConfig('app.web.share');
        //ppd(self::$_appOwner);
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
