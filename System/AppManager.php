<?php
if (! defined ( "CGAF" ))
die ( "Restricted Access" );

abstract class AppManager extends StaticObject {
	private static $_instances = array ();
	private static $_initialized = false;
	/**
	 * Enter description here...
	 *
	 * @var IApplication
	 */
	private static $_activeApp = null;
	private static $_model;
	private static $_appInfos = array ();
	private static $_installedApps;

	public static function setActiveApp($appId) {
		if (is_object ( $appId ) && $appId instanceof IApplication) {

			$id = $appId->getAppId ();
			self::$_activeApp = $id;
			if (! isset ( self::$_instances [$id] )) {
				self::$_instances [$id] = $appId;
			}
			$appId = $id;
		}
		Session::set ( "__appId", $appId );
	}

	private static function getDefaultAppId() {
		return CGAF::getConfig ( "defaultAppId", "__cgaf" );
	}

	/**
	 * getInstance of application
	 *
	 * @param string $appName
	 * @return IApplication
	 */
	public static function getInstance($appId = null) {

		if ($appId instanceof IApplication) {
			$id = $appId->getAppId ();
			if (! $id) {
				throw new SystemException ( 'Unable to get application id from class ' . get_class ( $appId ) );
			}
			if (! isset ( self::$_instances [$id] )) {
				self::$_activeApp = $id;
				Session::set ( "__appId", $id );
				self::$_instances ["$id"] = $appId;
			}
			return $appId;
		}

		$appInfo = null;

		if ($appId == null) {
			if (self::$_activeApp) {
				$appId = self::$_activeApp;

			} else {
				$appId = Session::get ( "__appId", self::getDefaultAppId() );

				if ($appId !== null && $appId !== "") {
					$appInfo = self::getAppInfo ( $appId, false );
					if (! CGAF::isAllow ( $appId, ACLHelper::APP_GROUP )) {
						$appId = null;
					} elseif ($appInfo) {
						$appId = $appInfo->app_id;
					} else {
						throw new SystemException ( 'unable to get Application ' . $appId );
					}
				}
			}
		}
		if (!$appId) {
			throw new SystemException ( 'unable to get Application ' . $appId );
		}
		if (isset ( self::$_instances [$appId] )) {
			return self::$_instances [$appId];
		}

		if ($appInfo == null) {
			$appInfo = self::getAppInfo ( $appId, false );

			if (! $appInfo) {
				return null;
			}
		}
		//$appName = $appInfo->app_class_name ? $appInfo->app_class_name : $appInfo->app_name;
		$appShortName = $appInfo->app_path ? $appInfo->app_path : $appInfo->app_name;
		$cName = $appInfo->app_class_name ? $appInfo->app_class_name : CGAF_CLASS_PREFIX . ($appInfo->app_path ? $appInfo->app_path : $appInfo->app_name) . "App";
		$clsfile = Utils::ToDirectory ( CGAF_APP_PATH . DS . $appInfo->app_path . DS . "app.class" . CGAF_CLASS_EXT );
		if (is_file ( $clsfile )) {
			CGAF::Using ( $clsfile );
		}
		$appPath=null;
		if (! class_exists ( $cName, false )) {
			$appPath = CGAF_APP_PATH . DS . $appInfo->app_path . DS . "index" . CGAF_CLASS_EXT;
			if ($appInfo->app_id==='__cgaf' && String::isEmpty($appInfo->app_path)) {
				$appPath = CGAF_APP_PATH.DS.'desktop'.DS.CGAF_CONTEXT.DS. "index" . CGAF_CLASS_EXT;
				$cName ="cgafdesktopapp";
			}

			$appFileName = Utils::ToDirectory ( $appPath );
			if (is_file ( $appFileName )) {
				require ($appFileName);
			} else {
				$alt = CGAF_APP_PATH . DS . $appInfo->app_path . DS . $appShortName . CGAF_CLASS_EXT;
				if (is_file ( $alt )) {
					$appFileName = $alt;
					$appPath = dirname($alt);
					require $alt;
				} else {
					Logger::Error ( "Applcation File Not Found." . Logger::WriteDebug ( "  @%s | %s" ), $appFileName, $alt );
				}
			}
		}

		if (! class_exists ( $cName, false )) {
			throw new SystemException ( "Class Application $cName Not Found" );
		}

		$appPath = String::isEmpty($appPath) ? CGAF_APP_PATH . DS . $appInfo->app_path . DS : dirname($appPath);
		CGAF::addStandardSearchPath(null, $appPath .DS. 'classes' ,false);
		CGAF::addClassPath ( 'apps', $appPath . 'classes' . DS );
		$instance = new $cName ( $appPath, $appInfo->app_name );
		$instance->setAppInfo ( $appInfo );


		if ($instance->Initialize ()) {
			Session::set ( '__appId', $appId );
			//CGAF::addAlowedLiveAssetPath($instance->getAppPath(true));
			self::$_instances [$appId] = $instance;
			if (! self::$_activeApp) {
				self::$_activeApp = $appId;
			}
			self::trigger("onAppLoaded",$instance);
			return self::$_instances [$appId];
		} else {
			throw new SystemException ( 'Unable to Initialize Application' );
		}

		return null;
	}

	public static function getAppInfo($id, $throw = true) {

		$id = $id ? $id : self::getDefaultAppId ();
		$direct = false;
		if ($id instanceof IApplication) {
			$id = $id->getAppId ();
			$direct = true;
		}
		if (isset ( self::$_appInfos [$id] )) {
			return self::$_appInfos [$id];
		}
		$model = self::getModel ()->clear ();
		if ($direct) {
			$model->setFilterACL ( FALSE );
		}
		$model->Where ( 'app_id=' . $model->quote ( $id ) );
		$o = $model->loadObject ();
		if ($o) {
			$infos [$o->app_id] = $o;
		} else {
			if ($throw) {
				throw new SystemException ( "Application $id not found" );
			} else {
				return null;
			}
		}
		return $infos [$o->app_id];
	}
	/**
	 *
	 * Enter description here ...
	 * @return String
	 */
	public static function getActiveApp() {
		return self::$_activeApp;
	}

	public static function getCurrentAppInfo() {
		return self::getAppInfo ( self::getInstance ()->getAppName (), true );
	}

	public static function isAppStarted() {
		return self::$_activeApp != null;
	}

	public static function Shutdown() {
		if (self::$_activeApp != null) {
			self::getInstance ()->Shutdown ();
		}
	}

	public static function getContent($position = null) {
		return self::getInstance ()->getContent ( $position );
	}

	public static function isAppInstalled($appPath) {
		//$q = CGAF::getConnector("applications");
		$o = self::getModel ()->Where ( "app_path=" . self::getModel ()->quote ( $appPath ) )->loadObject ();
		return $o;
	}

	public static function isAppIdInstalled($appId) {
		if ($appId === "desktop") {
			return true;
		}
		$q = CGAF::getConnector ( "applications" );
		$o = $q->Where ( "app_id=" . $q->quote ( $appId ) )->loadObject ();
		return $o;
	}

	private static function getAppClass($appName, $throw = true) {
		$cName = CGAF_CLASS_PREFIX . "{$appName}App";
		if (! class_exists ( $cName, false )) {
			$clsfile = Utils::toDirectory ( CGAF_APP_PATH . DS . $appName . DS . $appName . '.class' . CGAF_CLASS_EXT );
			if (is_file ( $clsfile )) {
				require $clsfile;
			}

			$appFileName = Utils::toDirectory ( CGAF_APP_PATH . DS . $appName . DS . "index" . CGAF_CLASS_EXT );
			if (is_file ( $appFileName )) {
				require ($appFileName);
			} else {
				$alt = Utils::toDirectory ( CGAF_APP_PATH . DS . $appName . DS . $appName . CGAF_CLASS_EXT );
				if (is_file ( $alt )) {
					require $alt;
				} elseif ($throw) {
					Logger::Error ( "Application File Not Found." . Logger::WriteDebug ( "  @%s | %s" ), $appFileName, $alt );
				} else {
					Logger::Warning ( "Application File Not Found." . Logger::WriteDebug ( "  @%s | %s" ), $appFileName, $alt );
				}
			}
		}
		if (! class_exists ( $cName, false )) {
			$cName = 'MVCApplication';
		}
		return $cName;
	}

	public static function getAppConfig($path) {
		global $_configs;
		$_configs = null;
		$cf = Utils::ToDirectory ( $path . DS . "config.php" );
		$config = new Configuration ( $_configs, false );

		if (is_file ( $cf )) {
			include ($cf);
			$config->Merge ( $_configs, true );
		}
		unset ( $_configs );
		$config->setConfigFile ( $cf );
		return $config;
	}

	/**
	 *
	 * Enter description here ...
	 * @param String $appName
	 * @throws Exception
	 * @return String id
	 */
	public static function install($appName) {
		$appPath = realpath ( CGAF_APP_PATH . DS . $appName . DS );
		$config = self::getAppConfig ( $appPath );
		$id = $config->getConfig ( "app.id" );

		if (! $id) {
			$id = GUID::getGUID ();
			$config->setConfig ( "app.id", $id );
			$config->Save ();
		}

		if (! self::isAppInstalled ( $appName )) {

			if (self::isAppIdInstalled ( $id )) {

				return $id;
			}

			$cl = self::getAppClass ( $appName, false );

			if ($cl) {
				Response::write("Installing $appPath $appName" );
				$class = new $cl ( $appPath, $appName );
				if ($class->Install ()) {
					$app = self::getModel ()->clear ();

					$app->app_id = $id;
					$app->app_class_name = $cl;
					$app->active = true;
					$app->app_name = $config->getConfig ( "app.name", $appName );
					$app->app_path = $appName;
					$app->app_version = $config->getConfig ( "app.version", "0.1" );
					$app->store ( false );
					//pp ( $app->LastSQL () );
					if ($app->getError ()) {
						throw new Exception ( $app->getError () );
					}
				}
			} else {
				Logger::Warning ( "Unable to find application class for $appName" );
			}
		}
		// elseif (CGAF_DEBUG) {
		//	throw new SystemException ( "Application already installed with id " . $id );
		//}
		return $id;
	}

	private static function getModel($clear = true) {
		if (! self::$_model) {
			self::$_model = new ApplicationModel ( CGAF::getDBConnection () );
		}
		if ($clear) {
			self::$_model->clear ();
		}
		return self::$_model;
	}

	public static function allowedApp() {
		$rows = AppManager::getInstalledApp ();
		return self::isAllowApp ( $rows );
	}

	public static function isAllowApp($o) {

		$acl = CGAF::getACL ();
		if (is_array ( $o )) {
			$r = array ();
			foreach ( $o as $v ) {
				$v = self::isAllowApp ( $v );
				if ($v) {
					$r [] = $v;
				}
			}
			return $r;
		} elseif (is_object ( $o )) {

			if (self::isAllowApp ( $o->app_id )) {
				$path = self::getAppPath($o);
				if (!is_dir($path)) {
					return null;
				}
				return $o;
			}
		} else {

			if ($o === '__cgaf' || ( int ) $o === - 1) {
				return true;
			}
			return CGAF::isAllow ( $o, ACLHelper::APP_GROUP );
		}
	}

	public static function getInstalledApp() {
		if (self::$_installedApps == null) {
			$installed = self::getModel ()->clear ();

			if (CGAF::isInstalled ()) {
				$installed = $installed->where ( "active=" . $installed->quote ( '1' ) );
			}

			$installed = $installed->loadObjects ();

			if (CGAF::isInstalled ()) {
				self::$_installedApps = self::isAllowApp ( $installed );
			}
		}
		return self::$_installedApps;
	}

	protected static function isAppPathInstalled($path) {

		$installed = self::getInstalledApp ();
		foreach ( $installed as $app ) {
			if ($app->app_path == $path) {
				return true;
			}
		}
		return false;
	}

	public static function getNotInstalledApp() {

		$retval = array ();
		if (! CGAF::isAllow ( "manage", "System" )) {
			return $retval;
		}
		$files = Utils::getDirList ( CGAF_APP_PATH );

		foreach ( $files as $file ) {
			if (strpos ( $file, '.' ) !== false)
			continue;
			if (! self::isAppPathInstalled ( $file )) {
				$retval [] = $file;
			}
		}
		return $retval;
	}

	public static function initialize() {
		if (self::$_initialized) {
			return true;
		}
		/*ppd(CGAF::getConfigs('Session.configs'));
		 ini_set('session.use_cookies', '0');
		 ini_set ( "session.auto_start", false );
		 ini_set ( "session.use_only_cookies", false );*/

		Session::Start ();

		self::$_initialized = true;
	}

	public static function getAppPath($AppName = null) {
		if ($AppName === null) {
			$AppName = self::$_activeApp;
		}

		$obj = new stdClass ();
		if (! is_object ( $AppName )) {
			$obj = self::getAppInfo ( $AppName, false );
		} else {
			$obj = $AppName;
		}

		$path = null;
		if (isset ( $obj->app_id )) {
			if (is_dir ( $obj->app_path )) {
				$path = $obj->app_path;
			} else {
				$path = Utils::ToDirectory ( CGAF_APP_PATH . DS . $obj->app_path . DS );
			}

		} else {
			if (System::isWebContext ()) {
				throw new SystemException ( "cgaf_app_not_installed", $AppName );
			} else {
				return null;
			}
		}
		$path = Utils::ToDirectory ( $path );
		return $path;
	}
}
?>
