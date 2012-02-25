<?php
namespace System\Cache;
use System\Configurations\Configuration;

class CachedData extends Configuration {
	private $_file;

	function __construct() {
		parent::__construct(null, false);
	}
	function __destruct() {
		if ($this->_file) {
			\Utils::makeDir(dirname($this->_file));
			file_put_contents($this->_file, serialize($this->_configs));
		}
	}

	function read($f) {
		$this->_file = $f;
		$data = array();
		if (is_file($f)) {
			$data = unserialize(file_get_contents($f));
		}
		return $this->Merge($data, true);
	}
	function get($name, $def = null) {
		return $this->getConfig($name, $def);
	}
	function set($name, $value) {
		return $this->setConfig($name, $value);
	}
}
