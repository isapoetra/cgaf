<?php
namespace System\MVC;
use \Request;
use \CGAF,\AppManager;

abstract class MVCHelper {

	public static function Initialize() {
		CGAF::addClassPath ( 'Models', CGAF_SHARED_PATH.'Models'.DS );
		CGAF::addClassPath ( 'Controller', CGAF_SHARED_PATH.'Controllers'.DS, false );
		AppManager::bind('onAppLoaded',array('\\System\\MVC\\MVCHelper','onAppLoaded'));
	}
	public static function addSearchPath($basepath) {
		$arr = array("Models","Controllers","Views");
		foreach($arr as $k=>$v) {
			$path = Utils::ToDirectory($basepath.DS.$v);
			if (is_dir($path)) {
				CGAF::addStandardSearchPath ( $v, $path,false );
			}elseif (CGAF::isDebugMode()){
				Logger::Warning('No standar path @'.$basepath.' for '.$v);
			}
		}


	}
	public static function onAppLoaded($appInstance) {
		if ($appInstance instanceof  \IApplication) {
			
		}
	}
	public static function getRoute($var = null) {
		static $route;
		$var = $var ? $var : (isset ( $_REQUEST ["__url"] ) ? $_REQUEST ["__url"] : null);
		$retval = array (
				"_a" => "index",
				"_c" => "home" );
		if ($var !== null) {
			$var_array = explode ( "/", $var ? $var : null );
			if (! empty ( $var_array [0] )) {
				$retval ["_c"] = $var_array [0];
			}
			if (! empty ( $var_array [1] )) {
				$retval ["_a"] = $var_array [1];
			}
		} else {
			if ($c = Request::get ( '__c' ,null,false)) {
				$retval ["_c"] = $c;
			}
			if ($c = Request::get ( '__a', null, false )) {
				$retval ["_a"] = $c;
			}
		}
		return $retval;
	}

	public static function route($c) {
		return BASE_URL . "/" . $c;
	}

	public static function getController($var = null, $appName = null) {
		$route = self::getRoute ( $var );
		$file = null;
		$app = AppManager::getInstance ( $appName );
		$appPath = $app->getAppPath ();
		$file = Utils::ToDirectory ( $appPath . DS . "Controllers/" . $route ["_c"] . "/Controller.php" );
		if (is_readable ( $file )) {
			include $file;
			$class = $route ["_c"] . "Controller";
			if (class_exists ( $class, false )) {
				return new $class ();
			}
		} else {
			return null;
		}
	}

	public static function getRouteORI($mod) {
		$route = self::getRoute ();
		if ($mod) {
			$route = array_merge ( $route, $mod );
		}
		$vars = Request::gets ( 'g' );
		$g = '';
		foreach ( $vars as $k => $v ) {
			$g .= "$k=" . htmlentities ( $v ) . "&";
		}

		return BASE_URL . '/' . $route ['_c'] . '/' . $route ['_a'] . '/?' . $g;
	}

	public static function lookup($name, $appOwner = null) {
		static $lookup;
		$appId = - 1;
		if ($appOwner == null) {
			$appOwner = AppManager::getInstance ();
			$appId = $appOwner->getAppId ();
		} else {
			if (! is_object ( $appOwner )) {
				$appId = $appOwner;
				$appOwner = AppManager::getInstance ();
			}
		}
		if (! $lookup) {
			$lookup = $appOwner->getModel ( 'lookup' );
		}
		$rows = $lookup->setIncludeAppId ( false )->clear ()->select ( 'key' )->select ( 'value' )->select ( 'descr' )->where ( 'app_id=' . $lookup->quote ( $appId ) )->where ( 'lookup_id=' . $lookup->quote ( $name ) )->loadObjects ();
		return $rows;
	}

	public static function getModel($model) {
		if (AppManager::isAppStarted ()) {
			$app = AppManager::getInstance ();
			if ($app instanceof MVCApplication) {
				return $app->getModel ( $model );
			}
		}
		throw new SystemException ( 'Application Not Started' );
	}

	public static function loadMVCClass($class) {
		if (String::EndWith ( $class, 'Model' )) {
			if (!CGAF::Using ( 'Models.' . substr ( $class, 0, strlen ( $class ) - 5 ),false )) {
				return CGAF::Using ( 'Models.' . strtolower(substr ( $class, 0, strlen ( $class ) - 5 )) ,false);
			}
			return true;
		}

	}
}
?>