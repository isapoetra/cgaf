<?php
namespace System\Auth;
use System\Events\LoginEvent;
use System\ACL\ACLHelper;
use System\Session\Session;
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
	abstract function authDirect($username, $password, $method = 'local');
	/**
	 * Enter description here ...
	 * @param unknown_type $configName
	 * @param unknown_type $def
	 */
	function getConfig($configName, $def) {
		return $this->_appOwner->getConfig('auth.' . $configName, $def);
	}
	protected function getAuthArgs($args = null) {
		$req = array(
				"username" => "",
				"password" => "",
				"remember" => "");
		if ($args == null) {
			$args = \Request::gets();
		}
		$retval = new \stdClass();
		$err = array();
		foreach ($args as $k => $v) {
			if (array_key_exists($k, $req)) {
				$retval->$k = $v;
			}
		}
		return $retval;
	}
	protected function setAuthInfo(AuthResult $info) {
		Session::remove("__auth");
		Session::remove("__logonInfo");
		if ($info && $info->getStatus() === AuthResult::SUCCESS) {
			Session::set("__logonInfo", $info);
		}
	}
	/* (non-PHPdoc)
	 * @see System.IAuthentificator::Logout()
	 */
	public function Logout() {
		Session::restart();
		$this->getAppOwner()->dispatchEvent(new LoginEvent($this, LoginEvent::LOGOUT));
		\Response::forceContentExpires();
	}
	/**
	 * (non-PHPdoc)
	 * @see System.IAuthentificator::getAuthInfo()
	 */
	function getAuthInfo() {
		if ($this->isAuthentificated()) {
			return Session::get("__logonInfo", null);
		}
		return null;
	}
	function encryptPassword($p) {
		return $p;
	}
	function generateRandomPassword($length = 8, $encrypt = true) {
		$chars = "abcdefghijkmnopqrstuvwxyz023456789";
		srand((double) microtime() * 1000000);
		$i = 0;
		$pass = '';
		while ($i <= $length) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $encrypt ? $this->encryptPassword($pass) : $pass;
	}
	/**
	 * (non-PHPdoc)
	 * @see System.IAuthentificator::isAuthentificated()
	 */
	function isAuthentificated() {
		return is_object(Session::get("__logonInfo", null)) && ACLHelper::getUserId() !== ACLHelper::PUBLIC_USER_ID;
	}
}
?>