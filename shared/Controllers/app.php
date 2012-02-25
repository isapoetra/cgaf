<?php
namespace System\Controllers;
use System\Session\Session;
use System\MVC\Controller;
use Request;
class AppController extends Controller {
	function Index() {
		$route = $this->getAppOwner ()->getRoute ();
		switch ($route ['_a']) {
			case 'index' :
				return parent::Index ();
			default :
				$app = \AppManager::getInstanceByPath ( $route ['_a'] );
				if ($app) {
					$r = array ();
					foreach ( $_REQUEST as $k => $v ) {
						if ($k !== '__url') {
							$r [$k] = $v;
						}
					}
					Session::set ( '__appId', $app->getAppId () );
					\Response::Redirect ( \URLHelper::addParam ( BASE_URL, $r ) );
					return;
				}
				break;
		}
		return parent::Index ();
	}
}
