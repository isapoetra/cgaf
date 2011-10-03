<?php
namespace System\Auth;
use System\Session\Session;
use System\Auth\Adapter\db;
use System\IAuthentificator;
use \Request;
use \CGAF, \Utils;
class Local extends BaseAuthentificator implements IAuthentificator {
	/**
	 *
	 * Enter description here ...
	 * @var IAuthentificatorAdapter
	 */
	private $_adapter;
	/**
	 * Enter description here ...
	 * @param IApplication $appOwner
	 */
	function __construct(\IApplication $appOwner) {
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

	function encryptPassword($p) {
		return Utils::getCryptedPassword($p, null, $this->getConfig('encryption.method', 'md5-hex'));
	}
	function Authenticate($args = null) {
		$this->setLastError(null);
		if ($args == null) {
			$args = Request::gets(null, true);
		}
		$args = parent::getAuthArgs();
		$adapter = $this->getAdapter();
		$adapter->setIdentify($args->username);
		$adapter->setCredential($this->encryptPassword($args->password));
		if ($result = $adapter->authenticate()) {
			$states = $result->getStates();
			if ($states) {
				Session::setStates($states);
			}
			\Logger::info("Login", $result->idetify, true);
			parent::setAuthInfo($result);
			return $result;
		}
		$this->setLastError("Invalid Username/password");
		return false;
	}
	function Logout() {
		$adapter = $this->getAdapter();
		$adapter->logout();
		return parent::Logout();
	}
}
?>