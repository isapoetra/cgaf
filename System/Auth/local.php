<?php
namespace System\Auth;

use System\Session\sessionStateHandler;

use System\Session\Session;
use System\Auth\Adapter\db;
use System\IAuthentificator;
use \Request;
use \Utils;
use System\Applications\IApplication;
class Local extends BaseAuthentificator implements IAuthentificator {
	/**
	 *
	 * Enter description here ...
	 *
	 * @var IAuthentificatorAdapter
	 */
	protected $_adapter;

	/**
	 * @param IApplication $appOwner
	 */
	function __construct(IApplication $appOwner) {
		parent::__construct($appOwner);
	}

	function getAdapter() {
		if (!$this->_adapter) {
			$this->_adapter = new db($this->getAppOwner());
			if ($this->isAuthentificated()) {
				$info = $this->getAuthInfo();
				$this->_adapter->setIdentify($info->getIdentify());
			}
		}
		return $this->_adapter;
	}

	function authDirect($username, $password, $method = 'local', $remember = false) {

		$adapter = $this->getAdapter();
		$adapter->setIdentify($username);
		$adapter->setCredential($password);
		$adapter->SetLogonMethod($method);
		$adapter->setRemember($remember);
		$result = $adapter->authenticate();
		if ($result) {
			$states = $result->getStates();
			if ($states) {
				if (!$states instanceof sessionStateHandler) {
					$states = new sessionStateHandler($states);
				}
				Session::setStates($states);
			}
			\Logger::info("Login", $result->idetify, true);
			parent::setAuthInfo($result);
		}
		return $result;
	}

	function encryptPassword($p) {
		return Utils::getCryptedPassword($p, null, $this->getConfig('encryption.method', 'md5-hex'));
	}

	function Authenticate($args = null) {
		$this->setLastError(null);
		if ($args == null) {
			$args = Request::gets(null, true);
		}
		$args = parent::getAuthArgs($args);
		$result = $this->authDirect($args->username, $this->encryptPassword($args->password), 'local', $args->remember);
		if (!$result) {
			$this->setLastError("Invalid Username/password");
			return false;
		}
		return true;
	}

	function Logout() {
		$adapter = $this->getAdapter();
		$adapter->logout();
		return parent::Logout();
	}
}

?>