<?php
namespace System\Auth;
use \AppManager;
class AuthResult extends \BaseObject {
	const FAILURE = 0;
	const SUCCESS = 1;
	private $_status;
	private $_identify;
	private $_userInfo;
	private $_states;
	private $_logonMethod;
	function __construct($result, $identify, $states, $userInfo, $logonMethod = 'local') {
		$this->_status = $result;
		$this->_identify = $identify;
		$this->_states = $states;
		$this->_userInfo = $userInfo;
		$this->_logonMethod = $logonMethod;
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
			throw new \Exception('userinfo not set');
		}
		return $this->_userInfo;
	}
	function getUserId() {
		$ui = $this->getUserInfo();
		return $ui ? $ui->user_id : -1;
	}
}
