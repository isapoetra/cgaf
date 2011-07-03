<?php
abstract  class MVCModule extends Object implements IAppModule {
	abstract function handleService($serviceName);
	protected static $_appOwner;
	protected static $_keyID;
	protected static $_stateGroup;
	protected static $_mod_name = null;

	public static function loadClass($className) {
		if (self::$_mod_name && String::BeginWith ( $className, self::$_mod_name )) {
			ppd ( $className );
		}

		return false;
	}
	public static function Initialize(IApplication $appOwner) {
		self::$_stateGroup = self::$_stateGroup ? self::$_stateGroup : __CLASS__;
		self::$_appOwner = $appOwner;
		$path = $appOwner->getAppPath ( true );
		CGAF::addStandardSearchPath ($appOwner->getAppName().'modules', $path . 'modules',false );
		
		CGAF::RegisterAutoLoad ( 'AppModule::loadClass' );
		
	}
	/**
	 *
	 * Enter description here ...
	 * @param IApplication $appOwner
	 * @deprecated use Initialize
	 */
	public static function Init(IApplication $appOwner) {
		self::Initialize($appOwner);
		
		///ppd($appOwner);
	}

	protected static function setKeyID($value) {
		self::$_keyID = $value;
	}

	public static function getState($name) {
		return Session::getState ( self::$_stateGroup, $name );
	}

	public static function getAppOwner() {
		return self::$_appOwner;
	}

	public static function loadModuleClass($m, $u = null, $a = null, $owner = null) {
		static $loaded;

		$m = ModuleManager::getModuleInfo ( $m );
		if (! $m) {
			return;
		}
		if (! $loaded) {
			$loaded = array ();
		}
		$xowner = $owner ? $owner : self::$_appOwner->getAppId();
		if (! is_string ( $owner ) && ! $owner == null) {
			$xowner = $owner->AppId;
		}
		$key = $m->mod_id . $u . $a . $xowner;
		if (isset ( $loaded [$key] ))
		return;
		$loaded [$key] = true;
		//$_addpath = null;
		$u = $u == null ? "" : $u;
		$a = $a == null ? "" : $a;
		$app = $owner ? $owner : AppManager::getInstance ();
		$paths = ModuleManager::getModulePath ( $m, $u, $a );

		$fc = Utils::ToDirectory(self::$_appOwner->getAppPath().DS . "modules" . DS . $m->mod_dir . DS . $m->mod_dir . ".class.php");
		if (is_file ( $fc )) {
			CGAF::Using ( $fc );
		}
		$fc = Utils::ToDirectory($m->mod_path . $m->mod_dir . ".class.php");
		if (is_file ( $fc )) {
			CGAF::Using ( $fc );
		}
		$modpath = self::$_appOwner->getAppPath(). "modules" . DS . $m->mod_dir.DS;
		if (is_dir($modpath)) {
			MVCHelper::addSearchPath($modpath);
			
		}
		$appclass = $app->BaseClass;
		if (class_exists ( $appclass . $m->mod_dir, false ) && is_callable ( array (
		$appclass . $m->mod_dir,
				"Initialize" ) )) {
		
		call_user_func ( array (
		$appclass . $m->mod_dir,
					"Initialize" ), $app );
				} elseif (class_exists ( $m->mod_dir, false ) && is_callable ( array (
				$m->mod_dir,
				"Init" ) )) {
				call_user_func ( array (
				$m->mod_dir,
					"Initialize" ), $app );
				}


				//CGAF::addStandardSearchPath("Models", $m->mod_dir);
	}
}