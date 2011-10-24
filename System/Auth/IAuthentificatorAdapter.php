<?php
namespace System\Auth;
interface IAuthentificatorAdapter {
	function setIdentify($value);
	function setCredential($value);
	function SetLogonMethod($value);
	/**
	 *
	 * Enter description here ...
	 * @return AuthResult
	 */
	function authenticate();

}