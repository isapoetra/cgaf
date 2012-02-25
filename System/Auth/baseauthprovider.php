<?php
namespace System\Auth;
abstract class BaseAuthProvider implements IAuthProvider {
	private $_appOwner;
	protected $_realUser;
	protected $_lastError;
	protected $_isAuthentificated = false;
	private $_state = Auth::NEED_RETRY_STATE;
	function __construct(\IApplication $appOwner) {
		$this->_appOwner = $appOwner;

	}
	abstract function getRemoteUser();
	abstract  function Initialize();
	protected function getAppOwner() {
		return $this->_appOwner;
	}
	function getRealUser() {
		return $this->_realUser;
	}
	protected function setState($state) {
		$this->_state = $state;
	}
	function getState() {
		return $this->_state;
	}
	function getLastError() {
		return $this->_lastError;
	}
	/**
	 * (non-PHPdoc)
	 * @see System\Auth.IAuthProvider::isAuthentificated()
	 */
	function isAuthentificated() {
		return $this->_isAuthentificated;
	}
}
