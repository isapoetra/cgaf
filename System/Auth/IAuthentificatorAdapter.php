<?php
namespace System\Auth;
interface IAuthentificatorAdapter {
	function setIdentify($value);
	function setCredential($value);
	/**
	 *
	 * Enter description here ...
	 * @return AuthResult
	 */
	function authenticate();
}