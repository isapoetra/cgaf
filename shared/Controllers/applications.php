<?php
namespace System\Controllers;
use System\Session\Session;
use System\MVC\Controller;
use \Request;
class ApplicationsController extends Controller {
	function Index() {

		$route = $this->getAppOwner()->getRoute();
		switch ($route['_a']) {
		case 'index':
			return parent::Index();
		default:
			/*$app=$route['_a'];
			$url = explode('/', $_REQUEST['__url']);
			array_shift($url);
			if (count($url)) {
				$app = $url[0];
				if (isset($url[1])) {
					Request::set('__c', $url[1]);
				}
				if (isset($url[2])) {
					Request::set('__a', $url[2]);
				}
			}
			$capp= \AppManager::getInstance();

			if ($app) {
				Request::set('__appId', $app->getAppId());
				\CGAF::Run($app);
			}
			ppd($app);
			pp($url);
			pp($route);
			ppd($_SERVER);*/
			$app = \AppManager::getInstanceByPath($route['_a']);
			if ($app) {
				$r = array();
				foreach($_REQUEST as $k=>$v) {
					if ($k !=='__url') {
						$r[$k] = $v;
					}
				}
				Session::set('__appId', $app->getAppId());
				\Response::Redirect(\URLHelper::addParam(BASE_URL,$r));
				return;
			}
			break;
		}
		return parent::Index();
	}
}
