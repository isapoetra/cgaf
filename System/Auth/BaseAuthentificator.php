<?php
namespace System\Auth;
use System\IAuthentificator;

abstract class BaseAuthentificator extends \Object implements IAuthentificator {
	private $_appOwner;
	private $_lastError = null;

	function __construct(\IApplication $appOwner) {
		$this->_appOwner = $appOwner;
	}

	public function getLastError() {
		return $this->_lastError;
	}

	function getAppOwner() {
		return $this->_appOwner;
	}

	protected function setLastError($value) {
		$this->_lastError = $value;
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $configName
	 * @param unknown_type $def
	 */
	function getConfig($configName, $def) {
		return $this->_appOwner->getConfig('auth.' . $configName, $def);
	}

	/* (non-PHPdoc)
	 * @see System.IAuthentificator::Authenticate()
	 */
	function Authenticate($args = null) {
		$req = array(
			"username" => "", "password" => "", "remember" => "");
		if ($args == null) {
			$args = Request::gets();
		}
		$err = array();
		foreach ($args as $k => $v) {
			if (array_key_exists($k, $req)) {
				if (empty($v)) {
					$err[] = "Empty $k";
				} else {
					$req[$k] = $v;
				}
			}
		}
		if (count($err)) {
			CGAF::addMessage("Authentification Error");
			CGAF::addMessage($err);
			return false;
		} else {
			$c = new stdClass();
			$c->user_id = -1;
			$c->user_name = $args['username'];
			return $c;
		}
		return true;
	}

	/* (non-PHPdoc)
	 * @see System.IAuthentificator::Logout()
	 */
	public function Logout() {}

}
?>