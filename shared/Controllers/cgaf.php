<?php
namespace System\Controllers;
use System\MVC\Controller;
use System\ACL\ACLHelper;
use \CGAF;
use \AppManager;
use \Request;
use \Response;

class CgafController extends Controller {
	function isAllow($access = 'view') {
		switch (strtolower($access)) {
			case 'view':
			case 'reset':
			case 'devmode':
				return cgaf::getConfig('app.debugmode');
		}
		return parent::isAllow($access);
	}
	function getControllerName() {
		return 'cgaf';
	}
	function reset() {
		cgaf::getACL()->onSessionDestroy(null);
		if (cgaf::isDebugMode()) {
			//perform acl to destroy
			cgaf::getACL();
			Session::destroy();
		}
		Response::Redirect('/?__t=' . time());
	}
	function installApp() {
		if (ACLHelper::isInrole(ACLHelper::DEV_GROUP)) {
			$id = Request::get('id');
			if ($id) {
				$appId = AppManager::install($id);
				Response::Redirect(URLHelper::addParam(BASE_URL, array(
						'__appId' => $appId)));
				return;
			}
		}
	}
	function devmode() {
		$dt = \Request::get('__devtoken');
		if ($dt === cgaf::getConfig('app.devtoken')) {
			setcookie('__devtoken', md5($dt),0,'/');
		} else {
			setcookie('__devtoken', null);
		}
		\Response::Redirect();
	}
}
?>
