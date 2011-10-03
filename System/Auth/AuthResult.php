<?php
namespace System\Auth;
use \AppManager;
class AuthResult extends \Object {
	const FAILURE = 0;
	const SUCCESS = 1;
	private $_status;
	private $_identify;
	private $_userInfo;
	private $_states;
	function __construct($result, $identify,$states) {
		$this->_status = $result;
		$this->_identify = $identify;
		$this->_states = $states;
	}

	function getStatus() {
		return $this->_status;
	}

	function getIdentify() {
		return $this->_identify;
	}
	function getStates() {
		return $this->_states;
	}
	function getUserInfo() {
		if (!$this->_userInfo) {
			$this->_userInfo = AppManager::getInstance()->getModel('user')->loadByIdentify($this->_identify);
		}
		return $this->_userInfo;
	}

	function getUserId() {
		$ui = $this->getUserInfo();
		return $ui ? $ui->user_id : -1;
	}
}
