<?php
namespace System\Auth;
use System\ACL\ACLHelper;
use System\Session\Session;
use System\IAuthentificator;
use System\Applications\IApplication;
abstract class BaseAuthentificator extends \BaseObject implements IAuthentificator {
	private $_appOwner;
	function __construct(IApplication $appOwner) {
		$this->_appOwner = $appOwner;
	}

	function getAppOwner() {
		return $this->_appOwner;
	}
	protected function setLastError($value) {
		$this->_lastError = $value;
	}
	abstract function authDirect($username, $password, $method = 'local');
	/**
   * @param $configName
   * @param $def
   * @return mixed
   */
	function getConfig($configName, $def) {
		return $this->_appOwner->getConfig('auth.' . $configName, $def);
	}
	protected function getAuthArgs($args = null) {
		$req = array(
				"username" => "",
				"password" => "",
				"remember" => false);
		if ($args == null) {
			$args = \Request::gets('p');
		}

		$retval = new \stdClass();
		$err = array();
		foreach ($args as $k => $v) {
			if (array_key_exists($k, $req)) {
				$retval->$k = $v;
			}
		}
		foreach ($req as $k=>$v) {
			if (!isset($retval->$k)) {
				$retval->$k=$v;
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
		//$this->getAppOwner()->dispatchEvent(new LoginEvent($this, LoginEvent::LOGOUT));
		//\Response::forceContentExpires();
		return true;
	}

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