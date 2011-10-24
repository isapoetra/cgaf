<?php
namespace System\Auth;
interface IAuthProvider {
	/**
	 *
	 * check if provider has authentificated
	 * @return boolean
	 */
	function isAuthentificated();
	/**
	 *
	 * Enter description here ...
	 * @see Auth state constant
	 */
	function getState();
	/**
	 *
	 * Enter description here ...
	 */
	function getLastError();
}
