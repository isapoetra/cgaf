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
	private $_credential;
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

	function logout() {
		$o = $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_state>=1")->loadObject();
		if ($o) {
			$this->clear();
			$this->Update('user_status', 0);
			$this->Update('states', serialize(Session::getStates()));
			$this->exec();
		}
	}

	function authenticate() {
		$o = $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_password=" . $this->quote($this->_credential))->Where("user_state>=1")->loadObject();
		if ($o) {
			$this->clear();
			$this->Update('last_access', \CDate::Current());
			$this->Update('last_ip',\System::getRemoteAddress());
			$this->exec();
			return new AuthResult(AuthResult::SUCCESS, $this->_identify, unserialize($o->states), $o, $this->_logonMethod);
		}
		return false;
	}
}
