<?php
namespace System\Auth\Adapter;

use System\Session\Session;
use System\Auth\AuthResult;
use System\Auth\IAuthentificatorAdapter;
use System\DB\Table;
use \CGAF;
use System\Applications\IApplication;
class db extends Table implements IAuthentificatorAdapter {
	private $_identify;
	protected $_credential;
	private $_logonMethod;
	private $_remember = false;

	function __construct(IApplication $appOwner) {
		if ($appOwner->getConfig("app.internalAuthentification", false)) {
			$connection = $appOwner;
		} else {
			$connection = CGAF::getDBConnection();
		}
		parent::__construct($connection, 'users', 'user_id');
	}

	function setIdentify($value) {
		$this->_identify = $value;
	}

	function setRemember($value) {
		$this->_remember = $value;
	}

	function setCredential($value) {
		$this->_credential = $value;
	}

	function validate(AuthResult $res) {
	}

	function SetLogonMethod($value) {
		$this->_logonMethod = $value;
	}
	function getUserInfo() {
		return $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_state>=1")->loadObject();
	}
	function logout() {
		$o = $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_state>=1")->loadObject();
		if ($o) {
			$this->clear();
			$this->Update('user_status', 0);
			$this->Update('states', serialize(Session::getStates()));
			$this->exec();
			$this->getAppOwner()->LogUserAction('logout', array($_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_PORT"]));
		}
	}
	protected function checkAuth() {
		return $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_password=" . $this->quote($this->_credential))->Where("user_state>=1")->loadObject();
	}
	function authenticate() {
		$o = $this->checkAuth();
		if ($o) {
			$this->clear();
			$this->Update('last_access', \CDate::Current());
			$this->Update('last_ip', \System::getRemoteAddress());
			$this->exec();
			$this->getAppOwner()->LogUserAction('login', array($_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_PORT"]), $o->user_id);
			return new AuthResult(AuthResult::SUCCESS, $this->_identify, $o->states ? @unserialize($o->states) : new \stdClass(), $o, $this->_logonMethod);
		}
		return false;
	}
}
