<?php
defined ( 'CGAF_BEGIN_TIME' ) or define ( 'CGAF_BEGIN_TIME', microtime ( true ) );
if (! defined ( "CGAF" )) {
	define ( "CGAF", true );
}
if (! defined ( 'CGAF_CLASS_PREFIX' )) {
	define ( 'CGAF_CLASS_PREFIX', '' );
}
//Define Cosntant
define ( "DS", DIRECTORY_SEPARATOR );


defined ( 'CGAF_VERSION' ) or define ( 'CGAF_VERSION', '1.0.1b' );
include "Logger.php";

final class CGAF {
	private static $_initialized = false;
	private static $_namespaces = array ();
	private static $_messages;
	private static $_classPath = array ();
	private static $_lastError;
	private static $_msgTitle;
	private static $_autoLoadCallBack = array ();
	private static $_acl;
	private static $_dbConnection;
	private static $_locale;
	private static $_cacheManager;
	private static $_benchmark;
	private static $_nsClass = array ();
	private static $_isDebugMode = false;
	private static $_installMode = false;
	private static $_shutdown = false;
	private static $_nsDebug = array ();
	private static $_searchPath = array ();
	private static $_allowedLivePath =array();
	/**
	 *
	 * @var IConfiguration
	 */
	private static $_configuration;

	public static function shutdown_handler() {
		static $shut;
		if ($shut) {
			return;
		}
		$shut = true;
		if (class_exists ( 'Response', false )) {
			Response::Flush ();
		}
		if (class_exists ( 'AppManager', false )) {
			AppManager::Shutdown ();
		}
		if (class_exists ( 'Logger', false )) {
			Logger::Flush ();
		}
		self::$_initialized = false;
		self::$_shutdown = true;
	}

	public static function startTime() {
		return self::$_benchmark;
	}

	public static function isFunctionExist($f, $throw = false) {
		if (! function_exists ( $f )) {
			if ($throw) {
				throw new SystemException ( 'function: ' . $f . ' Not Exist' );
			}
			return false;
		}
		return true;
	}

	public static function doExit() {
		static $exited;
		if ($exited) {
			return;
		}
		$exited = true;
		if (class_exists ( 'Response', false )) {
			if (! System::isConsole()) {
				Response::clearBuffer ();
			}
		}
		self::shutdown_handler ();
		exit ( 0 );
	}

	public static function setMessageTitle($msg) {
		self::$_msgTitle = $msg;
	}

	public static function addMessage($msg) {
		if (is_array ( $msg )) {
			foreach ( $msg as $m ) {
				self::$_messages [] = $m;
			}
		} else {
			self::$_messages [] = $msg;
		}
	}

	public static function getMessageTitle() {
		return self::$_msgTitle ? self::$_msgTitle : "Server Messages";
	}

	public static function getMessages() {
		return self::$_messages;
	}

	public static function getLastError() {
		return self::$_lastError;
	}

	public static function error_handler($errno, $errstr, $errfile, $errline) {

		self::$_lastError = "$errno@$errfile [$errline] : $errstr";
		Logger::write(self::$_lastError,$errno);
	}

	public static function exception_handler($ex) {
		if (self::$_shutdown) {
			ppd ( $ex );
			return false;
		}

		if (class_exists ( "AppManager", false )) {
			if (AppManager::isAppStarted ()) {
				return AppManager::getInstance ()->handleError ( $ex );
			}
		}
		Logger::Error ( "[%s] %s", get_class ( $ex ), $ex->getMessage () );
		self::doExit ();
	}



	static function getConfig($name, $def = null) {
		if ($name === 'installed') {
			return self::isInstalled ();
		}
		switch (strtolower ( $name )) {
			case 'disableacl' :
				$retval = self::geConfiguration ()->getConfig ( $name, $def );
				return CGAF_DEBUG ? $retval : false;
				break;
			default :
				;
				break;
		}
		return self::geConfiguration ()->getConfig ( $name, $def );
	}

	static function isInstalled() {
		return self::geConfiguration ()->getConfig ( 'installed', false );
	}

	/**
	 * return IConfiguration
	 */
	static function &geConfiguration() {
		global $_configs;
		if (self::$_configuration == null) {
			using ( "System.Configuration" );
			include CGAF_PATH . "config.php";
			self::$_configuration = new Configuration ( $_configs );
			unset ( $_configs );
		}
		return self::$_configuration;
	}

	static function getConfigs($group) {
		return self::geConfiguration ()->getConfigs ( $group );
	}

	public static function isInitialized() {
		return self::$_initialized;
	}

	public static function isDebugMode() {
		return self::$_isDebugMode;
	}

	public static function isRemoteDebugAllow() {
		static $debug;
		if ($debug == null) {
			$hosts = explode ( ',', self::getConfig ( 'debug.allowedhost', $_SERVER ['HTTP_HOST'] . ',' . $_SERVER ['SERVER_ADDR'] ) );
			//pp($hosts);
			//ppd($_SERVER ['REMOTE_ADDR']);
			$debug = in_array ( $_SERVER ['REMOTE_ADDR'], $hosts );

		}
		return $debug;
	}

	/**
	 * Enter description here...
	 *
	 * @return boolean
	 */
	static function Initialize() {
		if (self::$_initialized) {
			return true;
		}

		set_time_limit ( 0 );
		if (! defined ( "CGAF_PATH" )) {
			define ( "CGAF_PATH", realpath ( dirname ( __FILE__ ) . "/.." ) . DS );
		}

		//TODO Configurable
		//date_default_timezone_set ( 'Asia/Jakarta' );
		//self::$_benchmark = time () + microtime ();
		//ini_set ( "session.auto_start", false );
		if (! defined ( "CGAF_CLASS_PREFIX" )) {
			define ( "CGAF_CLASS_PREFIX", "T" );
		}

		if (! defined ( "SITE_PATH" )) {
			define ( "SITE_PATH", realpath ( dirname ( __FILE__ ) . "/../" ) . DS );
		}

		if (! defined ( "CGAF_CLASS_EXT" )) {
			define ( "CGAF_CLASS_EXT", ".php" );
		}

		self::$_searchPath =
		array ('System' => array (
		CGAF_PATH . 'System' . DS ) );
		System::Initialize();

		if (! defined ( "CGAF_CONTEXT" )) {
			if (defined ( 'STDIN' )) {
				$def = "Console";
			} else {
				$def = "Web";
			}
			define ( "CGAF_CONTEXT", self::getConfig ( "Context", $def ) );
		}

		if (!defined('BASE_URL')) {
			$s = null;
			if (CGAF_CONTEXT == "Web" && isset ( $_SERVER ['HTTP_HOST'] )) {
				/*** check for https ***/
				$protocol = isset ( $_SERVER ['HTTPS'] ) && ($_SERVER ['HTTPS'] == 'on') ? 'https' : 'http';
				/*** return the full address ***/
				$s = substr ( $_SERVER ['PHP_SELF'], 0, strripos ( $_SERVER ['PHP_SELF'], "/" ) );
				$s = $protocol . '://' . $_SERVER ['HTTP_HOST'] . ($_SERVER ["SERVER_PORT"] !== "8x" ? ":" . $_SERVER ["SERVER_PORT"] : "") . $s;
			}
			define('BASE_URL', self::getConfig ( 'baseurl', $s ));
		}
		define("ASSET_URL", self::getConfig("asseturl",BASE_URL.self::getConfig ( 'livedatapath', 'assets' ).'/'));
		ini_set ( "session.save_path", self::getInternalStorage ( 'sessions', false ) );
		register_shutdown_function ( "CGAF::shutdown_handler" );

		if (! defined ( "CGAF_APP_PATH" )) {
			define ( "CGAF_APP_PATH", self::getConfig ( 'applicationPath', CGAF_PATH . "Applications" ) );
		}
		if (CGAF_APP_PATH == null || realpath ( CGAF_APP_PATH ) == null) {
			die ( "Application Path Not Found" . CGAF_APP_PATH );
		}

		if (! defined ( 'CGAF_CORE_PATH' )) {
			define ( "CGAF_CORE_PATH", self::getConfig ( 'cgaf.core.path', CGAF_PATH . "Core" ) );
		}
		self::addNamespaceSearchPath('core', CGAF_CORE_PATH,false);


		$debugMode = self::getConfig ( 'DEBUGMODE', false );
		if ($debugMode) {
			if (! defined ( 'CGAF_DEBUG' )) {
				if (isset ( $_SERVER ['REMOTE_ADDR'] )) {
					self::$_isDebugMode = self::isRemoteDebugAllow ();
				} else {
					self::$_isDebugMode = false;
				}
			}
		}

		/**
		 *
		 * @var boolean
		 * @deprecated please use CGAF::isDebugMode
		 */
		if (! defined ( 'CGAF_DEBUG' )) {
			define ( 'CGAF_DEBUG', self::isDebugMode () );
		}
		//if (! CGAF_DEBUG) {
		set_error_handler ( "CGAF::error_handler" );
		set_exception_handler ( "CGAF::exception_handler" );
		//}
		if (CGAF_DEBUG) {
			define('CGAF_DEV_PATH',self::getConfig("cgaf.devpath",realpath(dirname(__FILE__).DS.'../../DevFiles/')).DS);
			self::addAlowedLiveAssetPath(CGAF_DEV_PATH.self::getConfig("assetpath",'assets'));
		}

		//ppd( self::getInternalStorage('log',false) . DS . 'cgaf.error.log' );
		$errors = self::getConfigs('errors'.(CGAF_DEBUG ? '.debug' : ''));
		foreach ($errors as $k=>$v) {
			if ($k==='debug') continue;
			switch ($k) {
				case 'log_errors':
					if ($v===null) {
						$v=true;
					}
					break;
				case 'error_log':
					if ($v===null) {
						$errors[$k] = self::getInternalStorage('log',false) . DS . 'cgaf.error.log';
					}
					break;
			}

		}
		if (CGAF_DEBUG) {
			self::$_configuration->setconfig('errors',$errors);
		}

		self::Using ( 'System.Utils' );
		self::Using ( 'System.String' );
		self::Using ( "System.Exceptions.*" );

		self::Using ( "System.Interface.*" );
		self::Using ( "System.Interface.DB.*" );
		self::addStandardSearchPath('Libs', self::getConfig('cgaf.libspath',CGAF_PATH.DS.'Libs'.DS),false);
		if (!defined("CGAF_VENDOR_PATH")) {
			define("CGAF_VENDOR_PATH", self::getConfig('cgaf.vendorpath',CGAF_PATH.'vendor'.DS),false);
		}
		self::addStandardSearchPath('Vendor', CGAF_VENDOR_PATH,false);

		if (!defined("CGAF_LIVE_PATH")) {
			define("CGAF_LIVE_PATH", CGAF_PATH);
		}
		self::addAlowedLiveAssetPath(CGAF_LIVE_PATH.self::getConfig("livedatapath","assets") );
		if (CGAF_DEBUG) {
			self::addAlowedLiveAssetPath(CGAF_DEV_PATH.self::getConfig("livedatapath","assets") );
		}
		//self::Using ( "System.AppModel.MVC.MVCApplication" );
		//die ( 'hi2' );
		$cn = self::getConfig ( "AppModel", "MVC" );

		self::Using ( "System.AppModel." . $cn.'.'.$cn.'Helper' );

		$hp = $cn."Helper";
		if (class_exists($hp)) {
			call_user_func ( array ( $hp,'Initialize' ) );
		}

		self::Using ( "System." . CGAF_CONTEXT . ".Context" );
		self::Using ( 'System.Collections' );
		$c = CGAF_CONTEXT . "Context";
		if (class_exists($c,false)) {
			call_user_func ( array ( $c,'Initialize' ) );
		}
		self::$_initialized = true;
		return true;
	}

	static function getTempPath() {
		return self::getConfig ( "temp.path", CGAF_PATH . 'tmp' . DS );
	}

	private static function offlineRedirect($code) {
		//TODO move to response
		if (! System::isConsole ()) {
			Response::redirect ( BASE_URL . 'offline.php?code=1' );
		} else {
			Response::writeln ( __ ( 'offline.' . $code, 'Offline : ' . $code ) );
			CGAF::doExit ();
		}
	}

	public static function onSessionEvent($event, $sid = null) {
		if (! ($event instanceof SessionEvent)) {
			throw new SystemException ( 'invalid event' );
		}

		$sender = $event->sender;
		$q = new DBQuery ( self::getDBConnection () );
		$sid = $sid ? $sid : $sender->getId ();

		$sess = new SessionModel ();

		switch ($event->type) {
			case SessionEvent::SESSION_GC :
			case SessionEvent::SESSION_STARTED :
				$lifetime = $sender->getConfig ( 'gc_maxlifetime' );
				$past = time () - $lifetime;

				$uid = self::getACL ()->getUserId ();
				if ($event->type == SessionEvent::SESSION_STARTED) {
					$q->clear ();

					$q->addSQL ( 'SELECT * from #__session  where session_id=' . $q->quote ( $sid ) );
					$o = $sess->load ( $sid );
					if (! $o || ! $o->session_id) {
						$q->clear ();
						$q->addTable ( 'session' );
						$q->addInsert ( 'user_id', $uid );
						$q->addInsert ( 'session_id', $sid );
						$q->addInsert ( 'client_id', $_SERVER ['REMOTE_ADDR'] );
						$q->addInsert ( 'last_access', $q->toDate () );
						$q->exec ();
					} else {
						$q->clear ();
						$q->addTable ( 'session' );
						$q->Where ( 'session_id=' . $q->quote ( $sid ) );
						$q->Update ( 'last_access', $q->toDate () );
						$q->update ( 'user_id', $uid );
						$q->exec ();
					}
				}
				$q->clear ()->addTable ( 'session' );
				$q->where ( 'last_access < ' . $q->quote ( $q->toDate ( $past ) ) );
				$q->delete ()->exec ();
				break;
			case SessionEvent::DESTROY :
				$q->clear ()->addTable ( 'session' )->where ( 'session_id=' . $q->quote ( $sid ) )->delete ();
				$q->exec ();
		}
	}
	private static function  handleAssetNotFound() {
		$f = $_REQUEST["__url"];

		$ext = Utils::getFileExt($f,false);
		$alt = null;

		$asset = str_ireplace('assets/', '', $f);
		$ass = AppManager::getInstance()->getAsset($asset);

		if ($ass && self::isAllowAssetToLive($ass)) {
			return Streamer::render($ass);
		}
		switch (strtolower($ext)) {
			case "png":
			case "jpg":
			case "gif":
			case "jpeg":
				$alt = SITE_PATH."assets/images/alts/empty.".$ext;
				break;
		}
		if ($alt && is_file($alt)) {
			return Streamer::render($alt);
		}
		throw new AssetException("asset not found %s",$f);
	}
	static function Run($appName = null, $installMode = false) {
		if (! self::Initialize ()) {
			die ( "unable to initialize framework" );
		}

		if (! self::isInstalled () && ! $installMode) {
			Response::redirect ( BASE_URL . 'Applications/Install/' );

			//return self::offlineRedirect(0);
		}

		if (self::getConfig ( 'offline' )) {
			self::offlineRedirect ( 1 );
		}
		AppManager::initialize ();
		if (isset($_REQUEST["__url"]) && String::BeginWith( $_REQUEST["__url"],"assets/")) {
			return self::handleAssetNotFound();
		}
		$retval = null;
		Session::getInstance ()->addEventListener ( '*', array (
				'CGAF',
				'onSessionEvent' ) );



		if (is_object ( $appName ) && $appName instanceof IApplication) {
			AppManager::setActiveApp ( $appName );
			$instance = $appName;
		} else {
			$appId = Request::get ( '__appId' );
			$instance = null;
			if ($appId) {
				if (AppManager::isAppIdInstalled ( $appId )) {
					Session::set ( '__appId', $appId );
					$appName = $appId;
				} else {

					throw new SystemException ( 'Application ' . $appId . 'not installed' );
				}
			}

			$cgaf = Request::get ( '__cgaf', null, true );
			switch (strtolower ( $cgaf )) {
				case 'reset' :
					Session::destroy ();
					Response::Redirect ( '/?__t=' . time () );
					return;
				case '_switchapp' :
					$appId = Request::get ( 'id' );
					if ($appId && AppManager::isAppIdInstalled ( $appId )) {
						Session::set ( '__appId', $appId );
						Response::Redirect ( '/' );
						return;
					}
					break;
				case '_installapp' :
					$id = Request::get ( 'id' );
					//check for security
					$appId = AppManager::install ( $id );
					Response::Redirect ( URLHelper::addParam ( BASE_URL, array (
							'__appId' => $appId ) ) );
					return;
					break;
				default :

					try {
						$instance = AppManager::getInstance ( $appName );

					} catch ( Exception $ex ) {
						if (CGAF_DEBUG) {
							throw $ex;
						}
						$instance = AppManager::getInstance ( 'desktop' );
					}
			}
		}

		if (! $instance) {

			die ( "Application Instance not found/Access Denied" );
		}

		Response::StartBuffer ();


		$retval = $instance->Run ();
		if ($retval) {
			Response::write ( $retval );
			Response::EndBuffer ( true );
		}
		return true;
	}
	public static function addNamespaceSearchPath($prefix, $path, $normalize = true) {

		$path = Utils::ToDirectory ( $path.DS );
		$prefix = $prefix ? $prefix : "__common";
		if (! isset ( self::$_searchPath [$prefix] )) {
			self::$_searchPath [$prefix] = array ();
		}
		if (! is_array ( $path )) {
			if (!is_dir($path)) {
				return;
			}
			$path = array ($path );
		}
		$spath = array ();
		if ($normalize) {
			foreach ( $path as $v ) {
				self::addStandardSearchPath($prefix, $v . 'classes' . DS,false);
				self::addStandardSearchPath($prefix, $v . DS,false);
			}
		} else {
			$spath = $path;
		}
		$paths = array();
		foreach($spath as $path) {
			if (!in_array($path, self::$_searchPath[$prefix]))  {
				$paths[] = $path;
			}
		}
		self::$_searchPath [$prefix] =array_merge($spath,self::$_searchPath[$prefix]);
	}
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $prefix
	 * @param unknown_type $path
	 * @param unknown_type $normalize
	 * @deprecated
	 */
	public static function addStandardSearchPath($prefix, $path, $normalize = true) {
		return self::addNamespaceSearchPath($prefix, $path,$normalize);
	}

	public static function addClassPath($nsName, $path) {
		$path = str_replace ( "/", DS, $path );
		$path = str_replace ( DS . DS, DS, $path );
		$nsName = strtolower ( $nsName );
		if (! array_key_exists ( $nsName, self::$_classPath )) {
			$r = array_merge ( array (
					"$nsName" => $path ), self::$_classPath );
			self::$_classPath = $r;
		}
	}

	public static function getClassPath($nsname = null) {

		if ($nsname) {
			$nsname = strtolower ( $nsname );
			return isset ( self::$_classPath [$nsname] ) ? self::$_classPath [$nsname] : null;
		}
		return self::$_classPath;
	}

	protected static function toNS($f) {
		$cpath = str_ireplace ( DS . DS, DS, CGAF_PATH . DS . "System" );
		$f = str_ireplace ( DS . DS, DS, $f );
		foreach ( self::$_classPath as $n => $v ) {
			$f = str_replace ( "$v.", "$n.", $f );
			$f = str_replace ( "$v", "$n.", $f );
		}

		$f = str_ireplace ( $cpath, "System.", $f);
		$f = str_ireplace ( CGAF_CLASS_EXT, "", $f );
		$f = str_ireplace ( "/", DS, $f );
		$f = str_ireplace ( DS, ".", $f );
		$f = str_ireplace ( "..", ".", $f );

		return $f;
	}

	private static function nsNormalize($ns, $p) {
		if (! class_exists ( "Utils", false )) {
			return $ns;
		}
		$p = Utils::ToDirectory ( $p );
		$rns = self::$_classPath [$ns];

		$p = str_replace ( $rns, "", $p );
		//$nns = substr ( $ns, 0, strpos ( $ns, "." ) );
		return $p;
	}

	private static function nsDebug($cat, $ns, $f) {
		self::$_nsDebug [$ns] [$cat] [] = $f;
	}

	private static function UsingDir($fname) {
		$dir = opendir ( $fname );
		while ( $d = readdir ( $dir ) ) {
			if (substr ( $d, 0, 1 ) !== "." && is_file ( $fname . DS . $d )) {
				$ext2 = substr ( $d, strlen ( $d ) - 3 );
				if (is_file ( $fname . DS . $d )) {
					self::Using ( $fname . DS . $d, false );
				}
			}
		}
		closedir ( $dir );
		return true;
	}

	private static function checkUsing($fname, $check = true) {
		$namespace = $fname;
		$fname = str_ireplace ( ".", DS, $fname );
		if (substr ( $fname, strlen ( $fname ) - 4, 1 ) == '.') {
			$ext = substr ( $fname, strlen ( $fname ) - 3 );
		} else {
			$ext = CGAF_CLASS_EXT;
		}

		if (is_file ( $fname . $ext )) {
			return self::Using ( $fname . $ext, false );
		} elseif (is_file ( $fname . CGAF_CLASS_EXT )) {
			return self::Using ( $fname . CGAF_CLASS_EXT, false );
		} elseif (is_dir ( $fname )) {
			return self::UsingDir ( $fname );
		} elseif ($check) {
			$spath = array (
			CGAF_PATH . "System",
			CGAF_PATH );
			if (class_exists ( "AppManager", false )) {
				if (AppManager::isAppStarted ()) {
					$path = AppManager::getInstance ()->getClassPath ();
					$spath = array_merge ( $spath, array (
					$path ) );

				}
			}

			foreach ( $spath as $path ) {
				if (self::checkUsing ( $path . DS . $fname . $ext, false )) {
					return TRUE;
				} elseif (self::checkUsing ( $path . DS . $fname, false )) {
					return true;
				}
			}
			foreach ( self::$_classPath as $k => $path ) {
				$nfname = Utils::ToDirectory ( $path . DS . $fname );
				//pp($nfname);
				if (self::checkUsing ( $nfname, false )) {
					return true;
				} elseif (String::BeginWith ( $namespace, $k, true )) {
					$tfname = Utils::ToDirectory ( $path . DS . substr ( str_replace ( '.', DS, $namespace ), strlen ( $k ) + 1 ) ) . CGAF_CLASS_EXT;
					if (self::Using ( $tfname, false )) {
						return true;
					}
				}
			}

			if (class_exists ( "AppManager", false )) {
				if (AppManager::isAppStarted ()) {
					return AppManager::getInstance ()->unhandledNameSpace ( $namespace );
				}
			}

		}
		return false;

	}

	private static function _toNS($ns) {
		//$ns = strtolower ( $ns );
		$lpos = strrpos ( $ns, '.', - 4 );
		$ext = substr ( $ns, $lpos );
		foreach (self::$_searchPath as $k=>$v) {
			foreach ($v as $p) {
				if (substr($ns, 0,strlen($p))===$p) {
					$ns = $k.'.'.substr($ns, strlen($p));
				}
			}
		}
		if ($ext === CGAF_CLASS_EXT) {
			$ns = substr ( $ns, 0, strlen ( $ns ) - strlen ( $ext ) );
		}
		$ns = str_replace ( CGAF_PATH, '', $ns );
		$ns = str_replace ( DS, '.', $ns );
		return $ns;
	}

	private static function _getFileOfNS($ns) {


		$first = substr ( $ns, 0, strpos ( $ns, '.' ) );
		$spath = isset ( self::$_searchPath [$first] ) ? self::$_searchPath [$first] : array ();
		$fns = str_replace ( '.', DS, substr ( $ns, strpos ( $ns, '.' ) + 1 ) );

		if (! is_array ( $spath )) {
			$spath = array (
			$spath );
		}
		if (isset(self::$_searchPath["__common"])) {
			$spath = array_merge($spath,self::$_searchPath["__common"]);
		}
		if (isset(self::$_searchPath["System"])) {
			$spath = array_merge($spath,self::$_searchPath["System"]);
		}
		$devs = array();
		if (is_dir ( $ns )) {
			$files = array();
			while ( false !== ($filename = readdir ( $dh )) ) {
				if (substr($filename, 0,1)!='.'){
					$files[] =$filename;
				}
			}
			return $files;

		}
		foreach ( $spath as $v ) {
			$fs = $v . $fns . CGAF_CLASS_EXT;
			$devs[]=$fs;
			if (is_file ( $fs )) {
				return $fs;
			}
			$fs = $v . strtolower($fns) . CGAF_CLASS_EXT;
			$devs[]=$fs;
			if (is_file ( $fs )) {
				return $fs;
			}
			$fs = $fs = $v . $fns . DS;
			$devs[]=$fs;
			if (is_dir ( $fs )) {
				$fs = Utils::getDirFiles ( $fs, $fs, FALSE, "/\\" . CGAF_CLASS_EXT . "/i" );
				return $fs;
			}
		}
		//pp($devs);
		/*foreach(self::$_classPath as $k=>$v) {
			$f = preg_replace("/$k/", $v, $ns);
			pp("$k $v $f $ns \n");
			}*/

	}

	public static function Using($namespace = null, $throw = true) {

		if ($namespace === null) {
			return self::$_namespaces;
		}
		if (is_array ( $namespace )) {
			$retval = array ();
			foreach ( $namespace as $k => $v ) {
				$retval [$k] = self::Using ( $v );
			}
			return $retval;
		}
		if (substr ( $namespace, strlen ( $namespace ) - 1 ) === '*') {
			$namespace = substr ( $namespace, 0, strlen ( $namespace ) - 2 );
		}

		$nsnormal = self::_toNS ( $namespace );
		if (isset ( self::$_namespaces [$nsnormal] )) {
			return $namespace;
		}

		if (is_file ( $namespace )) {
			self::$_namespaces [$nsnormal] = $namespace;
			require $namespace;
			return true;
		}

		$f = self::_getFileOfNS ( $nsnormal );
		if (is_array($f)) {
			foreach ($f as $v) {
				self::Using($v);
			}
			return true;
		}elseif ($f) {
			return self::Using ( $f );
		}
		if ($throw) {

			throw new Exception ( $namespace );
		}
		return false;

		if (is_array ( $namespace )) {
			$retval = array ();
			foreach ( $namespace as $n ) {
				$retval [$n] = self::using ( $n );
			}
			return $retval;
		}
		$ns = self::toNS ( $namespace );
		if (isset ( self::$_namespaces [$ns] )) {
			return self::$_namespaces [$ns];
		}
		$nsDir = false;
		if (substr ( $namespace, strlen ( $namespace ) - 1 ) == "*") {
			$namespace = substr ( $namespace, 0, strlen ( $namespace ) - 2 );

			$nsDir = true;
		}

		if ($nsDir && is_dir ( $namespace )) {
			return self::UsingDir ( $namespace );
		} elseif (is_file ( $namespace )) {
			if (substr ( $namespace, strrpos ( $namespace, "." ) ) === CGAF_CLASS_EXT) {
				self::nsDebug ( "File", $ns, $namespace );
				self::$_namespaces [$ns] = $namespace;
				require $namespace;
				return true;
			}
			return false;
		} else {

			return self::checkUsing ( $namespace );
		}

		return false;
	}

	/**
	 *
	 * @return IACL
	 */
	public static function getACL() {
		if (self::$_acl == null) {
			self::$_acl = ACLHelper::getACLInstance( "db",null);
		}
		return self::$_acl;
	}

	public static function getCacheManager() {
		if (self::$_cacheManager == null) {
			self::$_cacheManager = CacheFactory::getInstance();
		}
		return self::$_cacheManager;
	}

	public static function isAllow($o, $group) {
		return self::getACL ()->isAllow ( $o, $group );

	}
	public static function assetToLive($asset) {

		if (!self::isAllowAssetToLive($asset)) {
			return null;
		}

		if (Utils::isLive($asset)) {
			return Utils::PathToLive ($asset);
		}
		if (CGAF_DEBUG) {
			if (String::BeginWith( $asset,CGAF_DEV_PATH)) {
				$retval= str_ireplace(CGAF_DEV_PATH, CGAF::getConfig('cgaf.devurl',BASE_URL.'Devs/'),$asset);
				return $retval;
			}
		}
		return self::pathToLive($asset);

	}
	protected static function pathToLive($path) {
		pp($path);
		ppd(self::$_allowedLivePath);
		throw new Exception("x");
	}
	public static function addAlowedLiveAssetPath($path) {
		if (!in_array($path, self::$_allowedLivePath)) {
			self::$_allowedLivePath[] = $path;
		}

	}
	public static function isAllowAssetToLive($asset) {
		if (String::BeginWith ( $asset, 'https:' ) || String::BeginWith ( $asset, 'http:' ) || String::BeginWith ( $asset, 'ftp:' )) {
			return $asset;
		}
		if (!String::BeginWith ( $asset, self::$_allowedLivePath )) {
			ppd(self::$_allowedLivePath);
		}
		return true;
	}

	public static function isAllowFile($asset, $access = NULL) {
		$allow = array ();
		$asset = Utils::ToDirectory ( $asset );
		if (AppManager::isAppStarted ()) {
			$allow [] = AppManager::getInstance ()->getLivePath ();
			$allow [] = AppManager::getInstance ()->getTemporaryPath ();
		}

		$allow [] = self::getTempPath ();
		if (CGAF_DEBUG) {
			$allow [] = Utils::ToDirectory ( SITE_PATH . 'assets/compiled/' );
		}
		return String::BeginWith ( $asset, $allow );
	}

	public static function isShutdown() {
		return self::$_shutdown;
	}

	public static function RegisterAutoLoad($func) {
		if (! in_array ( $func, self::$_autoLoadCallBack )) {
			self::$_autoLoadCallBack [] = $func;
		}
	}

	public static function AddNamespaceClass($prefix, $ns) {
		self::$_nsClass [$prefix] = $ns;
	}

	private static function _getClassInstance($className, $suffix, $args, $find = true) {
		$cname = array();
		if (class_exists('AppManager',false) && AppManager::isAppStarted()) {
			$cname[]=AppManager::getInstance()->getAppName().$className.$suffix;
		}
		$suffix = strtolower ( $suffix );
		if (CGAF_CLASS_PREFIX) {
			$cname[] =
			CGAF_CLASS_PREFIX .$className . $suffix;
		}
		$cname[]= $className . $suffix;
		foreach ( $cname as $c ) {
			if (class_exists ( $c, false )) {
				return new $c ( $args );
			}
		}
	}

	public static function getClassInstance($className, $suffix, $args,$prefix =null) {
		$ci = self::_getClassInstance ( $className, $suffix, $args );
		if (! $ci) {
			//pp($suffix);
			$cpath = self::getClassPath ( $suffix );
			if (!$cpath) {
				return false;
			}
			foreach ( $cpath as $c ) {
				$cf = Utils::ToDirectory ( $c . DS . strtolower ( $className ) .'.class'. CGAF_CLASS_EXT );
				self::Using ( $cf, false );
				$ci = self::_getClassInstance ( $className, $suffix, $args );
				if ($ci) {
					return $ci;
				}
				$cf = Utils::ToDirectory ( $c . DS . strtolower ( $className ) . CGAF_CLASS_EXT );
				self::Using ( $cf, false );
				$ci = self::_getClassInstance ( $className, $suffix, $args );
				if ($ci) {
					return $ci;
				}
			}
		}
		return $ci;
	}

	public static function LoadClass($class, $throw = true) {

		foreach ( self::$_autoLoadCallBack as $func ) {
			if (call_user_func_array ( $func, array (
			$class ) )) {
				return true;
			}
		}
		foreach ( self::$_nsClass as $k => $v ) {
			if (substr ( $class, 0, strlen ( $k ) ) == $k) {
				if (self::Using ( $v . "." . substr ( $class, strlen ( $k ) ), false )) {
					return true;
				}
			}
		}
		if (class_exists ( 'AppManager', false )) {
			if (AppManager::isAppStarted ()) {
				if (AppManager::getInstance ()->loadClass ( $class )) {
					return true;
				}
			}
		}
		$_search = array (
				"System.$class" );
		if (defined ( "CGAF_CONTEXT" )) {
			$_search [] = "System." . CGAF_CONTEXT . ".{$class}";
			if (substr ( $class, 0, strlen ( CGAF_CLASS_PREFIX . CGAF_CONTEXT ) ) == CGAF_CLASS_PREFIX . CGAF_CONTEXT) {
				$_search = array_merge_recursive ( array (
						"System." . CGAF_CONTEXT . "." . substr ( $class, strlen ( CGAF_CLASS_PREFIX . CGAF_CONTEXT ) ) ), $_search );
			}
		}

		if (substr ( $class, strlen ( $class ) - strlen ( "exception" ) ) == "Exception") {
			$_search = array_merge_recursive ( array (
					"System.Exceptions." . $class ), $_search );
		}
		if (substr ( $class, 0, 1 ) == "I") {
			$_search = array_merge_recursive ( array (
					"System.Interface." . $class ), $_search );
		}

		foreach ( $_search as $ns ) {
			if (CGAF::Using ( $ns, false )) {
				return true;
			}
		}

		//test for not class prefix
		if (substr ( $class, 0, strlen ( CGAF_CLASS_PREFIX ) ) == CGAF_CLASS_PREFIX) {
			if (CGAF::Using ( "System." . substr ( $class, strlen ( CGAF_CLASS_PREFIX ) ), false )) {
				return true;
			}

		}

		if ($throw) {
			throw new SystemException ( "Class $class Not Found" );
		}
		return false;
	}

	public static function loadLibs($libName) {
		$libName = str_replace ( "/", ".", $libName );
		$libName = str_replace ( DS, ".", $libName );

		if (! self::Using ('Libs.'.$libName, true )) {
			self::Using ( "Libs.$libName.$libName" );
		}
	}


	/**
	 *
	 * Enter description here ...
	 * @return DBConnection
	 */
	public static function getDBConnection() {
		if (self::$_dbConnection == null) {
			self::using ( "System.DB.*" );
			$args = self::getConfigs ( "cgaf.db" );
			if (!$args) {
				$args  =self::getConfigs('db');
			}
			self::$_dbConnection = DB::Connect ( $args);
			self::$_dbConnection->setThrowOnError ( true );
		}
		return self::$_dbConnection;
	}

	/**
	 *
	 * @param $o
	 * @return IConnector
	 */
	public static function getConnector($o = null) {
		self::Using ( "System.DB.Query" );
		$q = new DBQuery ( self::getDBConnection () );
		if ($o) {
			return $q->addTable ( $o );
		}
		return $q;

	}

	/**
	 * @return TLocale
	 */
	public static function getLocale() {
		if (AppManager::isAppStarted ()) {
			return AppManager::getInstance ()->getLocale ();
		}
		if (self::$_locale == null) {
			//self::Using ( 'System.TLocale' );
			self::$_locale = new LC ( self::getConfig ( "locale.locale.default", "en" ), CGAF_PATH . DS . "locale" );
		}
		return self::$_locale;
	}

	public static function _($msg, $def = null) {
		if (class_exists ( 'AppManager', false )) {
			if (AppManager::getActiveApp () != null) {
				return AppManager::getInstance ()->getLocale ()->_ ( $msg, $def );
			}
		}
		return self::getLocale ()->_ ( $msg, $def );
	}



	public static function getInternalStorage($path=null, $checkApp = true) {
		static $istorage;
		if (! $istorage) {
			$istorage = Utils::ToDirectory ( CGAF_PATH . DS . self::getConfig ( 'app.internalstorage', 'protected' ) . DS );
		}
		$retval = null;
		if ($checkApp && AppManager::isAppStarted ()) {
			return AppManager::getInstance ()->getInternalStorage ( $path );
		} else {
			return $istorage . $path;
		}

	}
}
spl_autoload_register ( 'CGAF::LoadClass' );

function pp($o, $return = false) {


	if (class_exists('System',false) ) {
		if ($o === null) {
			if (System::isConsole ()) {
				$r = 'NULL';
			} else {
				$r = "<pre>NULL</pre>";
			}
		}else{
			if (! System::isConsole ()) {
				$r = "<pre>" . print_r ( $o, true ) . "</pre>";
			} else {
				$r = print_r ( $o, true );
			}
		}
	}else{
		$r =  print_r ( $o, true );
	}

	if (! $return) {
		if (class_exists ( 'Response', false )) {
			Response::write ( $r );
		} else {
			echo $r;
		}
	}
	if (class_exists('System',false)) {
		if (System::isConsole ()) {
			$r .= "\n";
		}
	}
	return $r;
}

function ppd($o, $clear = false) {
	if (class_exists('System',false)) {
		if (! System::isConsole ()) {
			header ( 'Content-Type: text/html' );
		}
	}
	echo "<pre>";
	var_dump ( $o );
	debug_print_backtrace ();
	echo "</pre>";
	if (class_exists ( "Response", false ) && ! CGAF::isShutdown ()) {
		if ($clear) {
			Response::clearBuffer ();
		}
		Response::write ( pp ( $o, true ) );
		Response::Flush ( true );
	} else {
		echo "<pre>";
		var_dump ( $o );
		debug_print_backtrace ();
		echo "</pre>";
	}
	CGAF::doExit ();
}

function using($namespace) {
	return CGAF::Using ( $namespace );
}

function ppbt() {
	echo '<pre>';
	debug_print_backtrace ();
	echo '</pre>';
}

/**
 *
 * @param String $s
 */
function __($s, $def = null) {
	return CGAF::_ ( $s, $def );
}

function ___($title, $args) {
	$args = func_get_args ();
	array_shift ( $args );
	return vsprintf ( __ ( $title ), $args );
}

function CGAFDebugOnly() {
	if (! CGAF_DEBUG) {
		throw new SystemException ( 'DEBUG ONLY' );
	}
}
//set library path
//set_include_path ( CGAF_PATH . "Libs" . PATH_SEPARATOR . get_include_path () );
?>
