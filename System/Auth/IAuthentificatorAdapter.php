<?php
namespace System\Auth;
interface IAuthentificatorAdapter {
	function setIdentify($value);
	function setCredential($value);
	function authenticate();
}