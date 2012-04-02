<?php
namespace System\Controllers;
use \Response;
use \Request;
use System\Session\Session;
use System\MVC\Controller;
use System\ACL\ACLHelper;
use AppManager;
class CGAFController extends Controller {
	function isAllow($access = 'view') {
		switch (strtolower ( $access )) {
			case 'view' :
			case 'reset' :
			case 'devmode' :
				return \cgaf::getConfig('cgaf.debugmode');
		}
		return parent::isAllow ( $access );
	}
	function getControllerName() {
		return 'cgaf';
	}
	function reset() {
		\CGAF::getACL ()->onSessionDestroy ( null );
		if (CGAF::isDebugMode ()) {
			// perform acl to destroy
			CGAF::getACL ();
			Session::destroy ();
		}
		Response::Redirect ( '/?__t=' . time () );
	}
	function installApp() {
		if (ACLHelper::isInrole ( ACLHelper::DEV_GROUP )) {
			$id = Request::get ( 'id' );
			if ($id) {
				$appId = AppManager::install ( $id );
				Response::Redirect ( \URLHelper::addParam ( BASE_URL, array (
						'__appId' => $appId
				) ) );
				return;
			}
		}
	}
	function devmode() {
		if (ACLHelper::isInrole ( ACLHelper::DEV_GROUP )) {
			$dt = \Request::get ( '__devtoken' );
			if ($dt === \CGAF::getConfig ( 'cgaf.devtoken' )) {
				setcookie ( '__devtoken', md5 ( $dt ), 0, '/' );
			} else {
				setcookie ( '__devtoken', null );
			}
		}
		\Response::Redirect ();

	}
}
?>
