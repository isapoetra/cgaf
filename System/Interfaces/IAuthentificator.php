<?php
namespace System;
use System\Auth\AuthResult;

interface IAuthentificator{
	/**
	 *
	 * Enter description here ...
	 * @param mixed $args
	 * @return AuthResult boolean
	 */
	function Authenticate($args=null);
	/**
	 *
	 * Enter description here ...
	 * @return boolean
	 */
	function Logout() ;
	/**
	 *
	 * Enter description here ...
	 * @return boolean
	 */
	function isAuthentificated();
	/**
	 *
	 * Enter description here ...
	 * @return AuthResult
	 */
	function getAuthInfo();
}
?>