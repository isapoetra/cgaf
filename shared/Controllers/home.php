<?php
namespace System\Controllers;
use System\MVC\Controller;
use System\ACL\ACLHelper;
/**
 * Basic Home Controller please extend another home from this class
 *
 * @author e1
 */
class Home extends Controller {
	function isAllow($view = 'view') {
		switch ($view) {
			case ACLHelper::ACCESS_VIEW :
			case 'applist' :
			case 'view' :
			case 'index' :
				return true;
		}
		return parent::isAllow ( $view );
	}
	function getAction($o, $id = null, $route = null,$params=null) {
		return null;
	}
}
?>
