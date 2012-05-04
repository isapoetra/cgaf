<?php
namespace System\Controllers;
use System\JSON\JSONResult;

use System\Exceptions\CGAFException;

use System\Exceptions\AccessDeniedException;
use System\Exceptions\SystemException;
use System\MVC\Controller;
class ApiController extends Controller {
	private $_apiKeys;
	private $_blockedIp=array();
	private $_apiKey;
	function isAllow($access = 'view') {
		switch ($access) {
			case 'view':
			case 'like':
			case 'auth':
			case 'request':
				return true;
			default:
				return false;
		}
	}
	function Initialize() {
		if (parent::Initialize()) {
			$this->_apiKey = \CGAF::getConfig('cgaf.apikey','454d5a32jkjskldja8374873894234nmasdasdjsak4324829343290489238423lkkasdjakljsdlasdklj');
			$f = \CGAF::getInternalStorage('data/apis/').'commons.api';
			if (!is_file($f)) {
				$this->_apiKeys = array();
			}else{
				$this->_apiKeys = unserialize(file_get_contents($f));
			}
			$f = \CGAF::getInternalStorage('data/apis/').'blocked.api';
			if (!is_file($f)) {
				$this->_blockedIp = array();
			}else{
				$this->_blockedIp = unserialize(file_get_contents($f));
			}

			return true;
		}
	}
	private function isBlocked() {
		return in_array($_SERVER['REMOTE_ADDR'], $this->_blockedIp);
	}
	private function getAPIKey() {
		return hash('sha256', $this->_apiKey.$_SERVER["REMOTE_ADDR"].time());
	}
	private function storeKey() {
		$key = $this->getAPIKey();
		$cr = new \CDate();
		$this->_apiKeys[$key] = array(
				'host'=>$_SERVER["REMOTE_ADDR"],
				'app'=>\AppManager::getInstance()->getAppId(),
				'ua'=>isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "",
				'date_registered'=>$cr->format(DATE_ISO8601),
				'valid_until'=>$cr->add(new \DateInterval('P1Y'))->format(DATE_ISO8601)
		);
		return $key;
	}
	function request() {
		if ($this->isBlocked()) {
			throw new AccessDeniedException("your host blocked by admin. please contact admin");
		}
		if (!$appId=\Request::get('__app')) {
			throw new CGAFException("Invalid Request");
		}
		$retval= $this->storeKey();
		if ($retval) {
			$key = $retval;
			$retval =$this->_apiKeys[$key];
			return array('_result'=>true,
					'row'=>array(
							'token'=>$key,
							'valid_until'=>$retval['valid_until'],
							'date_registered'=>$retval['date_registered']));
		}
	}
	function auth() {
		$auth = $this->getController('auth',false);
		if ($auth) {
			$auth = \AppManager::getInstance()->getAuthentificator();
			if ($auth->Authenticate()) {
				return new JSONResult(true, "Welcome");
			}
			throw new AccessDeniedException("Invalid Username or Password");
		}
		throw new CGAFException("Internal System Error,Please Contact Vendor");
	}
	function initAction($action, &$params) {
		if ($this->getAppOwner()->getRoute('_a')==='request') {
			return true;
		}
		if (!$this->isValidRemote()) {
			throw new AccessDeniedException("Invalid Api Token");
		}
		return parent::initAction($action, $params);
	}

	private function isValidRemote() {
		$remote = $_SERVER['REMOTE_ADDR'];
		$key = \Request::get('__apikey');
		if (!$key) {
			throw new AccessDeniedException("Invalid Api Token");
		}

		$skey =isset($this->_apiKeys[$key]) ? $this->_apiKeys[$key] :null;
		if (!$skey) {
			if (CGAF_DEBUG) {
				$this->storeKey();
			}
			throw new AccessDeniedException("Invalid Api Token");
		}
		$date = new \CDate();
		$rdate = new \CDate($skey['valid_until']);
		if ($date->getTimestamp() <= $date->getTimestamp()) {
			return true;
		}
		return false;
	}
	function __destruct() {
		$f = \CGAF::getInternalStorage('data/apis/').'commons.api';
		file_put_contents($f, serialize($this->_apiKeys));
		parent::__destruct();
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
