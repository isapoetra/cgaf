<?php
namespace System\Configurations;
use System\Applications\AbstractApplication;
class UserConfiguration extends Configuration {
	private $_uid;
	private $_configFile = 'config.json';
	private $_path = null;
	function __construct(AbstractApplication $appOwner, $uid, $configs = null) {
		parent::__construct ( $configs, false );
		$this->_path = $appOwner->getUserStorage ( $uid );
		if ($this->_path) {
			$this->_path .= $this->_configFile;
		}
	}
	protected function _canSetConfig($configName) {
		$configName = strtolower ( $configName );
		pp($configName);
		return false;
	}
	function __destruct() {
		if ($this->_path) {
			$this->Save ( $this->_path );
		}
	}
}
?>