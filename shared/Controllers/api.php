<?php
namespace System\Controllers;
use System\Exceptions\AccessDeniedException;
use System\Exceptions\SystemException;
use System\MVC\Controller;
class ApiController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'view':
		case 'like':
			return true;
		default:
			return false;
		}
	}
	function initAction($action, $params) {
		if (!$this->isValidRemote()) {
			throw new AccessDeniedException();
		}
		return parent::initAction($action, $params);
	}

	private function isValidRemote() {
		$remote = $_SERVER['REMOTE_ADDR'];
		$key = \Request::get('_key');
		if (!$key) {
			return false;
		}
		//TODO Validate Api Key
		return $key === '001' && $remote === '127.0.0.1';
	}
	function like() {
		$like = $this->getController('like');
		$method = \Request::get('method');
		$item = \Request::get('item');
		$type = \Request::get('type');
		$app = \Request::get('appId', '__cgaf');
		switch ($method) {
		case 'resume':
			$c = $like->getCount($type, $item, $app);
			return $c ? ___('like.count', $c) : '';
			break;
		case 'button':
			break;
		case 'like':
			if (!$like->isAllow('like'))
				throw new AccessDeniedException();
			return $like->like($type, $item, $app);
		default:
			;
			break;
		}
		ppd($like);
		ppd($_SERVER);
	}
}
