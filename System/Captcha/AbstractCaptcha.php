<?php

/**
 * AbstractCaptcha.php
 * User: e1
 * Date: 3/16/12
 * Time: 12:13 AM
 */
namespace System\Captcha;
use \System\Configurations\Configuration;
use System\Applications\IApplication;

abstract class AbstractCaptcha extends \BaseObject implements ICaptcha {
	/**
	 * @var \System\Applications\IApplication
	 */
	protected $_appOwner;
	/**
	 * @var Configuration $_configs;
	 */
	private $_configs;
	private $_prefix;
	protected $_errorMessage = null;
	private $_defaultConfigs;
	function __construct($prefix, IApplication $appOwner,$defaultConfig = array()) {
		$this->_appOwner = $appOwner;
		$this->_prefix = $prefix;
		$this->_defaultConfigs = $defaultConfig;
		$this->_initialize();
	}

	protected function _initialize() {
		$configs = $this->_appOwner->getConfigInstance()->getConfigs('captcha.' . $this->_prefix, $this->_defaultConfigs);
		if (!$configs) {
			$configs = \CGAF::getConfigs('captcha.' . $this->_prefix, array());
		}
		$this->_configs = new Configuration($configs, false);
	}

	function setConfig($configName, $value) {
		$this->_configs->setConfig($configName, $value);
	}

	function getConfig($configName, $default = null) {

		return $this->_configs->getConfig($configName, $default);
	}
}
