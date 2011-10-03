<?php
namespace System\Auth\Adapter;
use System\Session\Session;

use System\Auth\AuthResult;
use System\Auth\IAuthentificatorAdapter;
use System\DB\Table;
use \CGAF;
class db extends Table implements IAuthentificatorAdapter {
	private $_identify;
	private $_credential;
	function __construct(\IApplication $appOwner) {
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
	function setCredential($value) {
		$this->_credential = $value;
	}
	function validate(AuthResult $res) {
	}
	function logout() {
		$o = $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_status>=1")->loadObject();
		if ($o) {
			//$this->bind($o);
			$this->Update('user_state', 0);
			$this->Update('states', serialize(Session::getStates()));
			$this->exec();
		}
	}
	function authenticate() {
		$o = $this->clear()->Where("user_name=" . $this->quote($this->_identify))->Where("user_password=" . $this->quote($this->_credential))->Where("user_status>=1")->loadObject();
		if ($o) {
			return new AuthResult(AuthResult::SUCCESS, $this->_identify, unserialize($o->states));
		}
		return false;
	}
}
