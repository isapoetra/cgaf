<?php
namespace System\Compressor;
use System\Configurations\Configuration;

abstract class AbstractCompressor extends \Object {
	protected $_config;
	protected $_currentPath;
	function __construct() {
		$this->reset();
	}
	function setCurrentPath($value) {
		$this->_currentPath = $value;
	}
	protected abstract function getDefaultConfigs();
	function setConfigs($configs) {
		$this->_config->setConfigs($configs);
	}

	function reset() {
		$this->_prepared = false;
		$this->_config = new Configuration($this->getDefaultConfigs(), false);
	}
}
