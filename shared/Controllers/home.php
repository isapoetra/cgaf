<?php
namespace System\Controllers;
use System\MVC\Controller;

use System\ACL\ACLHelper;
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
	function getAction($o, $id = null, $route = null) {
		return null;
	}
}
?>
