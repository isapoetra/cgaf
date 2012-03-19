<?php
namespace System\Auth;
use System\Applications\IApplication;
use System\Session\Session;
abstract class BaseAuthProvider implements IAuthProvider {
	private $_appOwner;
	protected $_realUser;
	private $_lastError;
	protected $_isAuthentificated = false;
	protected $_remoteUser;
	private $_state = Auth::NEED_RETRY_STATE;
	private $_base;
	private $_sessionStorage;
	function __construct(IApplication $appOwner,$base) {
		$this->_appOwner = $appOwner;
		$this->_base =$base;

	}
	protected function getFromSession($var) {
		return Session::get($this->_sessionStorage.'.'.$var);
	}
	protected function setToSession($var,$val) {
		return Session::set('__auth.'.$this->_base.'.'.$var,$val);
	}
	abstract function getRemoteUser();
	function Initialize() {
		$this->_sessionStorage = '__auth.'.$this->_base;
		$this->_remoteUser = $this->getFromSession('remoteUser');
		return  true;
	}
	protected function getAppOwner() {
		return $this->_appOwner;
	}
	function reset() {
		//	$this->setState(Auth::NEED_RETRY_STATE);
		Session::remove($this->_sessionStorage);
	}
	function getRealUser() {
		if (!$this->_realUser && $this->_remoteUser) {
			$u = $this->_appOwner->getModel('user');
			$this->_realUser=$u->loadByExternalId($this->_remoteUser->id,$this->_base);			
		}
		return $this->_realUser;
	}
	protected function setRemoteUser($r) {				
		$this->setToSession('remoteUser', $r);		
		$this->_remoteUser = $r;
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
	function setLastError($value) {
		$this->setState(Auth::ERROR_STATE);
		$this->_lastError = $value;
	}
	/**
	 * (non-PHPdoc)
	 * @see System\Auth.IAuthProvider::isAuthentificated()
	 */
	function isAuthentificated() {
		return $this->_isAuthentificated;
	}
}
