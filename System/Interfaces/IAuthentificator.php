<?php
namespace System;
interface IAuthentificator{
	function Authenticate($args=null);
	function Logout() ;
}
?>