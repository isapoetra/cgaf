<?php
namespace System\Controllers;
use System\MVC\Controller;
use System\ACL\ACLHelper;
/**
 *
 * Basic Home Controller please extend another home from this class
 * @author e1
 *
 */
class HomeController extends Controller {
	public function isAllow($access = "view") {
		switch ($access) {
		case ACLHelper::ACCESS_VIEW;
		case 'applist':
		case 'view':
		case 'index':
			return true;
		}
		return parent::isAllow($access);
	}
	function getControllerName() {
		return 'home';
	}
	function getAction($o, $id = null, $route = null) {
		return null;
	}
}
?>
