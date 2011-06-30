<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );

class ModuleManager {
	private static $_moduleList;
	public static function getActiveModuleId() {
		$m = self::getModuleInfo ();
		if ($m) {
			return $m->mod_id;
		}
		return null;
	}

	public static function getActiveModules($appid = null) {
		static $list;
		$all = false;
		if ($appid === null) {
			$appid = AppManager::getActiveApp ();
			$all = true;
		}
		
		if (! isset ( $list [$appid] )) {
			$q = new DBQuery (CGAF::getDBConnection());
			$q->addTable ( "modules" );
			$q->Where ( "mod_state=1" );
			$q->Where ( "app_id=" . $q->quote ( $appid ) );
			
			$rows = $q->loadObjects ();
			$l = array ();
			
			$acl = AppManager::getInstance ()->getACL ();
			foreach ( $rows as $row ) {
				if (CGAF_DEBUG || $acl->isAllow ( $row->mod_id, 'modules', ACLHelper::ACCESS_READ )) {
					$l [] = self::getModuleInfo ( $row->mod_id );
				}
			}
			$list [$appid] = $l;
		}
		
		return $all ? $list : $list [$appid];
	}

	public static function getModuleInfo($m = null, $check = true, $app = null) {
		//static $list;
		if ($m == null) {
			$m = Request::get ( "__m" );
		}
		if ($m === null)
			return false;
		if (is_object ( $m ))
			return $m;
		
		$config = false;
		$app = AppManager::getInstance ( $app );
		$appId = $app->getAppId ();
		if (! self::$_moduleList) {
			if (CGAF_DEBUG) {
				CacheFactory::remove ( "module-list-$appId", "module", true, true );
			}
			$clist = CacheFactory::get ( "module-list-$appId", "module", true, true );
			if ($clist) {
				self::$_moduleList = unserialize ( $clist );
				if (count ( $list ) == 0) {
					self::$_moduleList = false;
				}
			}
			
			if (! self::$_moduleList) {
				self::$_moduleList = array ();
				$q = new DBQuery (CGAF::getDBConnection());
				$q->select ( "m.*,'' as mod_path" );
				$q->addTable ( "modules", "m" );
				$q->addJoin ( "applications", 'a', 'm.app_id=a.app_id' );
				$q->Where ( "mod_state=1" );
				$q->Where ( "app_state=1" );
				
				$q->where ( 'm.app_id=-1 or m.app_id=' . $q->quote ( $appId ) );				
				$lst = $q->loadObjects ();
				
				//configure mod app Path
				$path = CGAF_APP_PATH;
				
				foreach ( $lst as $k => $v ) {
					$npath = null;
					
					if ($v->mod_active && AppManager::isAllowApp ( $v->app_id )) {
						$path = AppManager::getAppPath ( $v->app_id );
						if (! $v->mod_dir) {
							$v->mod_dir = $v->mod_name;
						}
						if (( int ) $v->app_id == - 1) {
							$npath = CGAF_CORE_PATH . DS . "modules" . DS . $v->mod_dir.DS;
						} elseif ($v->mod_dir) {
							$npath = $path . DS . ($v->mod_path ? $v->mod_path . DS : "") . 'modules' . DS . $v->mod_dir.DS;
						}
						$npath = Utils::ToDirectory ( $npath );
						$f = '';
						$fmenu = false;
						if (! $v->mod_class_name) {
							$appInfo = AppManager::getAppInfo ( $v->app_id );
							$v->mod_class_name = CGAF_CLASS_PREFIX . ($appInfo && isset ( $appInfo->app_short_name ) ? $appInfo->app_short_name : "") . ucfirst ( $v->mod_name );
						}
						if ($v->mod_ui_icon) {
							$f = $app->getLiveData ( $npath . DS . "images" . DS . $v->mod_ui_icon );
							$icon = Utils::getFileName ( $v->mod_ui_icon, false ) . '-icon.png';
							$fmenu = $app->getLiveData ( $npath . DS . "images" . DS . $icon );
						}
						if (! $fmenu) {
							//ppd($npath . DS . "images" . DS . $v->mod_dir . '-icon.png');
							$fmenu = $app->getLiveData ( $npath . DS . "images" . DS . $v->mod_dir . '-icon.png' );
						}
						$v->menu_icon = $fmenu;
						$v->live_icon = $f;
						$v->mod_path = $npath;
						self::$_moduleList [$v->mod_id] = $v;
					}
				}
				$config = true;
			}
		}
		
		if ($config && $check && ! CGAF_DEBUG) {
			$tmp = array ();
			foreach ( self::$_moduleList as $k => $v ) {
				if (CGAF::getACL ()->isAllow ( $v->mod_id, 'modules' )) {
					$tmp [$k] = $v;
				}
			}
			self::$_moduleList = $tmp;
			CacheManager::putString ( serialize ( $tmp ), "module-list", "module", true, true );
		}
		//ppd($list);
		$retval = false;
		if (is_numeric ( $m )) {
			$retval = isset ( self::$_moduleList [$m] ) ? self::$_moduleList [$m] : false;
		} else {
			foreach ( self::$_moduleList as $k => $v ) {
				if ((strtolower ( $m ) == strtolower ( $v->mod_name ) || strtolower ( $m ) == strtolower ( $v->mod_dir )) && ($v->app_id == $appId || $v->app_id == - 1)) {
					$retval = $v;
					break;
				}
			}
		}
		return $retval;
	}

	public static function getInternalModules() {
		$sql = "select * from #__modules where mod_app_owner=-1 and mod_active=1";
		$list = DB::loadObjectLists ( $sql );
		$retval = array ();
		foreach ( $list as $v ) {
			$module = self::getModuleInfo ( $v->mod_id );
			if ($module) {
				$retval [] = $v;
			}
		}
		return $retval;
	}

	public static function loadModule($m, $u = null, $a = null) {
		$minfo = self::getModuleInfo ( $m );
		if (! $minfo) {
			throw new AccessDeniedException ( "Module " . $m );
		}
		$dosql = Request::get ( "_dosql" );
		$s = Request::get ( "_s" );
		self::loadModuleClass ( $minfo, $u, $a );
		//$_addpath= false;
		$f = self::getModuleFile ( $minfo, $u, $a );
		$app = AppManager::getInstance ();
		//CGAF::trace(__FILE__,__LINE__,E_NOTICE,$f,"module");
		if (is_file ( $f ) && (! $dosql && ! $s)) {
			CGAF::Using ( $f );
		} else {
			if ($s) {
				return $app->handleService ( true );
			} else if (! $dosql) {
				$alt = self::getModuleFile ( $minfo, $u, $a );
				$alt2 = $minfo->mod_path . DS . $minfo->mod_dir . ".php";
				Response::StartBuffer();
				if (is_file ( $alt )) {
					include  $alt ;
				} elseif (is_file ( $alt2 )) {
					include $alt2 ;
				} else {					
					echo $app->HandleModuleNotFound ( $m,$u,$a );
				}
				return Response::EndBuffer();
			} else {
				if (is_file ( $f )) {
					require ($f);
				} else {
					$app->HandleModuleNotFound ( $m, $f );
				}
			}
		}
		return true;
	}

	public static function isActiveModule($m) {
		$mods = self::getModuleInfo ( $m );
		return $mods;
	}

	public static function getModulePath($m, $u=null, $a=null, $appInstance = null) {
		static $tmp_path;
		if (! $tmp_path) {
			$tmp_path = array ();
		}
		$appInstance = $appInstance ? $appInstance : AppManager::getInstance ();
		$m = self::getModuleInfo ( $m );
		if (! $m) {
			return array ();
		}
		$a = $a ? $a : "";
		$u = $u ? $u : "";
		$dosql = Request::get ( "_dosql", "" );
		$key = $m->mod_id . $u . $a . $dosql;
		if (isset ( $tmp_path [$key] ))
			return $tmp_path [$key];
		$path = Utils::arrayExplode ( $u . "." . $a . "." . $dosql, ".", true, false );
		
		$s = Request::get ( "_s" );
		if ($s) {
			array_pop ( $path );
		}
		$retval = array ();
		$prev = "";
		
		//also check from active app
		$app_path = $appInstance->getAppPath () . DS . "modules" . DS . strtolower ( $m->mod_name ) . DS;
		foreach ( $path as $p ) {
			$prev .= $p . DS;
			$retval [] = $m->mod_path . $prev . DS . $p;
			$retval [] = $app_path . $prev . DS . $p;
		}
		if (count ( $path ) == 1) {
			$retval [] = $m->mod_path . $path [0];
		}
		$retval = array_reverse ( $retval, false );
		$retval [] = $app_path;
		//$retval [] = $m->mod_path . DS . String::FromLastPos ( Utils::ToDirectory ( $m->mod_path, false ), DS, 0 );
		

		$tmp_path [$key] = Utils::ToDirectory($retval);
		return $tmp_path [$key];
	}

	public static function getModuleFile($m, $u, $a, $ext = ".php") {
		$paths = self::getModulePath ( $m, $u, $a );
		foreach ( $paths as $path ) {
			$fname = $path . $ext;
			if (is_file ( $fname )) {
				return $fname;
			}
		}
		return null;
	}

	public static function mergeParam($u, $a, $dosql) {
		return ($u ? $u . "." : "") . ($a ? $a . "." : "") . ($dosql ? $dosql : "");
	}

	public static function getActionParam($u, $a, $dosql) {
		$r = self::mergeParam ( $u, $a, $dosql );
		$path = Utils::arrayExplode ( $r, "." );
		$s = Request::get ( "_s" );
		if ($s) {
			array_pop ( $path );
		}
		return count ( $path ) > 1 ? $path [count ( $path ) - 1] : "";
	}

	public static function loadModuleClass($m, $u = null, $a = null, $owner = null) {
		static $loaded;
		$m = self::getModuleInfo ( $m );
		if (! $m) {
			return;
		}
		if (! $loaded) {
			$loaded = array ();
		}
		$xowner = $owner;
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
		$paths = self::getModulePath ( $m, $u, $a );
		$fc = $m->mod_path . $m->mod_dir . ".class.php";
		
		if (is_file ( $fc )) {
			CGAF::Using ( $fc );
		}
		CGAF::Using ($m->mod_path . $m->mod_dir . ".func.php",false);
		
		$appclass = $app->BaseClass;
		if (class_exists ( $appclass . $m->mod_dir, false ) && is_callable ( array (
				$appclass . $m->mod_dir, 
				"Init" ) )) {
			call_user_func ( array (
					$appclass . $m->mod_dir, 
					"Init" ), $app );
		} elseif (class_exists ( $m->mod_dir, false ) && is_callable ( array (
				$m->mod_dir, 
				"Init" ) )) {
			call_user_func ( array (
					$m->mod_dir, 
					"Init" ), $app );
		}
		foreach ( $paths as $path ) {
			
			$cn = $m->mod_name;
			$path = Utils::ToDirectory ( $path, false );
			$xfname = String::FromLastPos ( $path, DS, null, false );
			$cn .= String::replace ( array (
					$m->mod_path, 
					DS ), "", $xfname );
			$cn = strtolower ( $cn );
			try {
				self::loadModuleClass ( $cn );
			} catch ( Exception $ex ) {
				
				ppd ( $ex );
			}
		
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param mixed $mod_id
	 * @return IAppModule
	 */
	public static function getInstance($mod_id) {
		$minfo = self::getModuleInfo ( $mod_id );
		$instance = null;
		if ($minfo) {
			self::loadModuleClass ( $mod_id );
			$cn = $minfo->mod_class_name;
			if (class_exists ( $cn )) {
				$instance = new $cn ();
			} else {
				throw new SystemException ( "cgaf_class_notfound", $cn );
			}
		}
		return $instance;
	}

	public static function UnInstall($mod, $appPath) {
		if (! ACL::checkPerm ( "system", "manage" ))
			return false;
		$appInfo = AppManager::getAppInfo ( $appPath );
		if (! $appInfo) {
			return false;
		}
		$minfo = self::getModuleInfo ( $mod, true, $appInfo->app_id );
		if ($minfo) {
			if (! ACL::RemoveModule ( $minfo->mod_id )) {
				Response::WriteLn ( "Uninstalling Module $mod   Failed" );
			}
			return true;
		} else {
			DB::Execute ( "delete from #__modules where mod_id='$mod'" );
		}
		return false;
	}
}
?>