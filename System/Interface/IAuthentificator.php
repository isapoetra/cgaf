<?php
if (!defined("CGAF")) die("Restricted Access");
interface IAuthentificator{
	function Authenticate($args=null);
	function Logout() ;
}
?>