<?php
namespace System\MVC;
use System\Web\Utils\HTMLUtils;
use System\Exceptions\SystemException;
use Request;
use \CGAF, \AppManager, \Utils, \Logger;

abstract class MVCHelper {
	private static $_route;

	public static function Initialize() {
		CGAF::addClassPath('Models', CGAF_SHARED_PATH . 'Models' . DS);
		CGAF::addClassPath('Controller', CGAF_SHARED_PATH . 'Controllers' . DS, false);
		AppManager::bind('onAppLoaded', array(
		                                     '\\System\\MVC\\MVCHelper',
		                                     'onAppLoaded'
		                                ));
	}

	public static function addSearchPath($basepath) {
		$arr = array(
			"Models",
			"Controllers",
			"Views"
		);
		foreach ($arr as $k => $v) {
			$path = Utils::ToDirectory($basepath . DS . $v);
			if (is_dir($path)) {
				CGAF::addStandardSearchPath($v, $path, false);
			} elseif (CGAF::isDebugMode()) {
				Logger::Warning('No standar path @' . $basepath . ' for ' . $v);
			}
		}
	}

	public static function onAppLoaded($appInstance) {
		if ($appInstance instanceof \IApplication) {
		}
	}

	public static function getRoute($var = null) {
		$var = $var ? $var : (isset ($_REQUEST ["__url"]) ? $_REQUEST ["__url"] : null);
		$retval = array(
			"_a" => "index",
			"_c" => "home"
		);
		if (!empty ($var)) {
			$var_array = explode("/", $var ? $var : null);
			if (!empty ($var_array [0])) {
				\Request::set('__c', $var_array [0]);
				$retval ["_c"] = $var_array [0];
			} else {
				$retval ["_a"] = \Request::get('__c', $retval ["_c"]);
			}
			if (!empty ($var_array [1])) {
				\Request::set('__a', $var_array [1]);
				$retval ["_a"] = $var_array [1];
			} else {
				$retval ["_a"] = \Request::get('__a', $retval ["_a"]);
			}
		} else {
			$c = \Request::get('__c', null, false);
			if ($c) {
				$retval ["_c"] = $c;
			}
			$c = \Request::get('__a', null, false);
			if ($c) {
				$retval ["_a"] = $c;
			}
		}
		$retval ["_a"] = strip_tags(HTMLUtils::removeTag($retval ["_a"]));
		$retval ["_c"] = strip_tags(HTMLUtils::removeTag($retval ["_c"]));
		return $retval;
	}

	public static function route($c) {
		return BASE_URL . "/" . $c;
	}

	public static function getController($var = null, $appName = null) {
		$route = self::getRoute($var);
		$file = null;
		$app = AppManager::getInstance($appName);
		$appPath = $app->getAppPath();
		$file = Utils::ToDirectory($appPath . DS . "Controllers/" . $route ["_c"] . "/Controller.php");
		if (is_readable($file)) {
			include $file;
			$class = $route ["_c"] . "Controller";
			if (class_exists($class, false)) {
				return new $class ();
			}
		} else {
			return null;
		}
	}

	public static function getRouteORI($mod) {
		$route = self::getRoute();
		if ($mod) {
			$route = array_merge($route, $mod);
		}
		$vars = Request::gets('g');
		$g = '';
		foreach ($vars as $k => $v) {
			$g .= "$k=" . htmlentities($v) . "&";
		}
		return BASE_URL . '/' . $route ['_c'] . '/' . $route ['_a'] . '/?' . $g;
	}

	public static function lookup($name, $appOwner = null) {
		$appId = CGAF::APP_ID;
		if ($appOwner == null) {
			$appOwner = AppManager::getInstance();
			$appId = $appOwner->getAppId();
		} else {
			if (!is_object($appOwner)) {
				$appId = $appOwner ? $appOwner : $appId;
				$appOwner = AppManager::getInstance();
			}
		}
		$lookup = $appOwner->getModel('lookup');
		$lookup->setIncludeAppId(false);
		$lookup->clear();
		$lookup->select('key');
		$lookup->select('value');
		$lookup->select('descr');
		$lookup->where('app_id=' . $lookup->quote($appId));
		$lookup->where('lookup_id=' . $lookup->quote($name));
		$rows = $lookup->loadObjects();
		/*
		 * foreach($rows as $row) { $row->value = __($row->value); }
		 */
		return $rows;
	}

	public static function getModel($model) {
		if (AppManager::isAppStarted()) {
			$app = AppManager::getInstance();
			if ($app instanceof Application) {
				return $app->getModel($model);
			}
		}
		throw new SystemException ('Application Not Started');
	}

	public static function loadMVCClass($class) {
		if (\Strings::EndWith($class, 'Model')) {
			if (!CGAF::Using('Models.' . substr($class, 0, strlen($class) - 5), false)) {
				return CGAF::Using('Models.' . strtolower(substr($class, 0, strlen($class) - 5)), false);
			}
			return true;
		}
	}
	public static function parse($url=null) {
		if (!$url) $url = \URLHelper::getOrigin();
		$url = \URLHelper::explode($url);
		$retval =array(
				'scheme'=>$url['scheme'],
				'host'=>$url['host'],
				'port'=>$url['port'],
				'_c'=>'home',
				'_a'=>'index');
		if ($url['path']) {
			$retval['_c'] = array_shift($url['path']);
			$retval['_a'] = array_shift($url['path']);	
			$retval['path'] =$url['path'];		
		}
		if (isset($url['query_params']['__c'])) {
			$retval['_c'] = $url['query_params']['__c'];
			unset($url['query_params']['__c']);
		}
		if (isset($url['query_params']['__a'])) {
			$retval['_a'] = $url['query_params']['__a'];
			unset($url['query_params']['__a']);
		}
		unset($url['query_params']['XDEBUG_SESSION']);
		$retval['params']=$url['query_params'];
		return $retval;
	}
	public static function toCGAFUrl($u) {
		$params='';
		if (isset($u['params'])) {
			foreach($u['params'] as $k=>$v) {
				$params.= $k.'='.htmlspecialchars($v).'&';
			}
			
		}
		$retval = $u['scheme'].'://'.$u['host'].'/'.($u['_c']==='home'  ? '' : $u['_c']).($u['_a'] ==='index'? '' :'/'.$u['_a']).(isset($u['path'])?implode('/',$u['path']).'/':'').($params ? '?'.$params : '');

		return $retval;
	}
}

?>