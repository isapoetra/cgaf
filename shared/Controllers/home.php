<?php
if (!defined("CGAF"))
	die("Restricted Access");
use System\ACL\ACLHelper;
class HomeController extends \System\MVC\Controller {
	function isAllow($view = 'view') {
		switch ($view) {
		case ACLHelper::ACCESS_VIEW;
		case 'applist':
		case 'view':
		case 'index':
			return true;
		}
		return parent::isAllow($view);
	}
	function getAction($o) {
		return null;
	}
}
?>
