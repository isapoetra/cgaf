<?php
namespace {
//Define Cosntant
defined('CGAF_BEGIN_TIME') or define('CGAF_BEGIN_TIME', microtime(true));
defined("CGAF") or define("CGAF", true);
defined('CGAF_CLASS_PREFIX') or define('CGAF_CLASS_PREFIX', '');
defined('DS') or define("DS", DIRECTORY_SEPARATOR);
defined('CGAF_VERSION') or define('CGAF_VERSION', '1.0');
use System\Configurations\Configuration as Configuration;
use AppManager as AppManager;
use System\Locale\Locale;
use System\Session\Session;
use \Logger;
use \Request;
use System\DB\DB;
use System\ACL\ACLHelper;
use \System\DB\DBQuery;
use \System\Exceptions\AssetException;
use System\Exceptions\SystemException;
use System\Session\SessionEvent;
final class CGAF {
	const APP_ID = '__cgaf';
	private static $_initialized = false;
	private static $_namespaces = array();
	private static $_messages;
	private static $_classPath = array();
	private static $_lastError;
	private static $_msgTitle;
	private static $_autoLoadCallBack = array();
	private static $_acl;
	private static $_dbConnection;
	private static $_locale;
	private static $_cacheManager;
	private static $_internalCache;
	private static $_benchmark;
	private static $_nsClass = array();
	private static $_isDebugMode = false;
	private static $_installMode = false;
	private static $_shutdown = false;
	private static $_nsDebug = array();
	private static $_searchPath = array();
	private static $_allowedLivePath = array();
	private static $loadedNamespaces = array();
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
		if (class_exists('Response', false)) {
			Response::Flush();
		}
		if (class_exists('AppManager', false)) {
			AppManager::Shutdown();
		}
		if (class_exists('Logger', false)) {
			Logger::Flush();
		}
		self::$_initialized = false;
		self::$_shutdown = true;
	}
	public static function startTime() {
		return self::$_benchmark;
	}
	public static function isFunctionExist($f, $throw = false) {
		if (!function_exists($f)) {
			if ($throw) {
				throw new SystemException('function: ' . $f . ' Not Exist');
			}
			return false;
		}
		return true;
	}
	private static function getCacheRequestFile($sessionBase = false) {
		$path = self::getInternalStorage('.cache/request/' . ($sessionBase ? session_id() . '/' : ''), false, true);
		$f = $path . DS . md5($_SERVER['REQUEST_URI']);
		return $f;
	}
	private static function exitIfNotModified() {
		if (self::getConfig('disablecacherequest')) {
			return;
		}
		$f = self::getCacheRequestFile(true);
		if (!is_file($f)) {
			$f = self::getCacheRequestFile(false);
		}
		//\Utils::removeFile($path = self::getInternalStorage('.cache/request', false, true), true);
		$valid = null;
		if (is_file($f)) {
			$fc = unserialize(file_get_contents($f));
			$last_modified = $fc['timeCreated'] >= $fc['lastModified'] ? $fc['timeCreated'] : $fc['lastModified'];
			$validUntil = $last_modified + (60 * 60 * 24 * $fc['validDay']);
			$valid = $fc['validDay'];
			if ($last_modified > $validUntil) {
				pp('removed');
				\Utils::removeFile($f);
				return;
			}
			//ppd(date('d M Y', $validUntil));
			if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
				$if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
				//ppd($if_modified_since.'-'.$validUntil);
				if ($if_modified_since <= $validUntil) {
					header_remove('Cache-Control');
					header('Cache-Control: public, max-age=' . ($valid * 30 * 24 * 60 * 60));
					header('Last-Modified:' . gmdate("D, d M Y H:i:s", $lastModified) . ' GMT');
					//ppd(gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')));
					header('Expires:' . gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')) . ' GMT');
					header("HTTP/1.0 304 Not Modified");
					CGAF::doExit();
					exit();
				}
			}
		}
	}
	public static function cacheRequest($lastModified, $valid, $sessionBase = false, $originalFile = null) {
		if (isset($_SERVER['REQUEST_URI'])) {
			$f = self::getCacheRequestFile($sessionBase);
			@file_put_contents($f, serialize(array(
					'originalURL' => $_SERVER['REQUEST_URI'],
					'timeCreated' => time(),
					'lastModified' => $lastModified,
					'validDay' => $valid,
					'originalFile' => $originalFile)));
			header_remove('pragma');
			header_remove('P3P');
			header_remove('X-Powered-By');
			header_remove('Cache-Control');
			header_remove('Last-Modified');
			header('Cache-Control: public, max-age=' . ($valid * 30 * 24 * 60 * 60));
			header('Last-Modified:' . gmdate("D, d M Y H:i:s", $lastModified) . ' GMT');
			//ppd(gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')));
			header('Expires:' . gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')) . ' GMT');
		}
	}
	private static function removeCacheRequest($uri = null) {
		$uri = $uri ? $uri : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null);
		if ($uri) {
			\Utils::removeFile(self::getCacheRequestFile(true));
			\Utils::removeFile(self::getCacheRequestFile(false));
		}
	}
	public static function doExit() {
		static $exited;
		if ($exited) {
			return;
		}
		$exited = true;
		if (class_exists('Response', false)) {
			if (!System::isConsole()) {
				Response::clearBuffer();
			}
		}
		self::shutdown_handler();
		exit(0);
	}
	public static function setMessageTitle($msg) {
		self::$_msgTitle = $msg;
	}
	public static function addMessage($msg) {
		if (is_array($msg)) {
			foreach ($msg as $m) {
				self::$_messages[] = $m;
			}
		} else {
			self::$_messages[] = $msg;
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
		if (class_exists('Logger')) {
			Logger::write(self::$_lastError, $errno);
		} elseif (CGAF_DEBUG) {
			pp(self::$_lastError);
		}
	}
	public static function exception_handler($ex) {
		if (self::$_shutdown) {
			ppd($ex);
			return false;
		}
		if (class_exists("AppManager", false)) {
			if (AppManager::isAppStarted()) {
				return AppManager::getInstance()->handleError($ex);
			}
		}
		Logger::Error("[%s] %s", get_class($ex), $ex->getMessage());
		self::doExit();
	}
	static function getConfig($name, $def = null) {
		if ($name === 'installed') {
			return self::isInstalled();
		}
		switch (strtolower($name)) {
		case 'disableacl':
			$retval = self::geConfiguration()->getConfig($name, $def);
			return CGAF_DEBUG ? $retval : false;
			break;
		default:
			;
			break;
		}
		return self::geConfiguration()->getConfig($name, $def);
	}
	static function isInstalled() {
		return self::geConfiguration()->getConfig('installed', false);
	}
	/**
	 * return IConfiguration
	 */
	static function &geConfiguration() {
		global $_configs;
		if (self::$_configuration == null) {
			include CGAF_PATH . "config.php";
			self::$_configuration = new Configuration($_configs);
			unset($_configs);
		}
		return self::$_configuration;
	}
	static function getConfigs($group) {
		return self::geConfiguration()->getConfigs($group);
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
			$hosts = explode(',', self::getConfig('debug.allowedhost', $_SERVER['HTTP_HOST'] . ',' . $_SERVER['SERVER_ADDR']));
			//pp($hosts);
			//ppd($_SERVER ['REMOTE_ADDR']);
			$debug = in_array($_SERVER['REMOTE_ADDR'], $hosts);
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
		set_time_limit(0);
		if (!defined("CGAF_PATH")) {
			define("CGAF_PATH", realpath(dirname(__FILE__) . "/..") . DS);
		}
		//TODO Configurable
		//date_default_timezone_set ( 'Asia/Jakarta' );
		//self::$_benchmark = time () + microtime ();
		//ini_set ( "session.auto_start", false );
		if (!defined("CGAF_CLASS_PREFIX")) {
			define("CGAF_CLASS_PREFIX", "T");
		}
		if (!defined("SITE_PATH")) {
			define("SITE_PATH", realpath(dirname(__FILE__) . "/../") . DS);
		}
		if (!defined("CGAF_CLASS_EXT")) {
			define("CGAF_CLASS_EXT", ".php");
		}
		define('CGAF_SYS_PATH', CGAF_PATH . 'System' . DS);
		self::addClassPath('system', CGAF_SYS_PATH);
		self::$_searchPath['System'] = array(
				CGAF_SYS_PATH);
		System::Initialize();
		if (!defined("CGAF_CONTEXT")) {
			if (defined('STDIN')) {
				$def = "Console";
			} else {
				$def = "Web";
			}
			define("CGAF_CONTEXT", self::getConfig("Context", $def));
		}
		if (!defined('BASE_URL')) {
			$s = self::getConfig('baseurl');
			if (!$s) {
				if (CGAF_CONTEXT == "Web" && isset($_SERVER['HTTP_HOST'])) {
					/*** check for https ***/
					$protocol = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
					/*** return the full address ***/
					$s = substr($_SERVER['PHP_SELF'], 0, strripos($_SERVER['PHP_SELF'], "/"));
					$port = ($_SERVER["SERVER_PORT"] !== "8x" ? ":" . $_SERVER["SERVER_PORT"] : "");
					if ($protocol === 'https' && $_SERVER["SERVER_PORT"] === '443') {
						$port = null;
					}
					$s = $protocol . '://' . $_SERVER['HTTP_HOST'] . $port . $s . '/';
				}
			}
			define('BASE_URL', $s);
		}
		define("ASSET_URL", self::getConfig("asseturl", BASE_URL . self::getConfig('livedatapath', 'assets') . '/'));
		ini_set("session.save_path", self::getInternalStorage('sessions', false));
		register_shutdown_function("CGAF::shutdown_handler");
		if (!defined("CGAF_APP_PATH")) {
			define("CGAF_APP_PATH", self::getConfig('applicationPath', CGAF_PATH . "Applications"));
		}
		if (CGAF_APP_PATH == null || realpath(CGAF_APP_PATH) == null) {
			die("Application Path Not Found" . CGAF_APP_PATH);
		}
		if (!defined('CGAF_SHARED_PATH')) {
			define("CGAF_SHARED_PATH", self::getConfig('cgaf.shared.path', CGAF_PATH . "shared") . DS);
		}
		self::addAlowedLiveAssetPath(CGAF_SHARED_PATH . self::getConfig("assetpath", 'assets'));
		\System\MVC\MVCHelper::Initialize();
		self::addClassPath('system', CGAF_SHARED_PATH);
		//self::addNamespaceSearchPath ( 'core', CGAF_SHARED_PATH, false );
		$debugMode = self::getConfig('DEBUGMODE', false);
		if ($debugMode) {
			if (!defined('CGAF_DEBUG')) {
				if (isset($_SERVER['REMOTE_ADDR'])) {
					self::$_isDebugMode = self::isRemoteDebugAllow();
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
		if (!defined('CGAF_DEBUG')) {
			define('CGAF_DEBUG', self::isDebugMode());
		}
		if (!CGAF_DEBUG) {
			error_reporting(E_ERROR | E_WARNING | E_PARSE);
			set_error_handler("CGAF::error_handler");
			set_exception_handler("CGAF::exception_handler");
		} else {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}
		if (CGAF_DEBUG) {
			define('CGAF_DEV_PATH', self::getConfig("cgaf.devpath", realpath(dirname(__FILE__) . DS . '../../DevFiles/')) . DS);
			self::addAlowedLiveAssetPath(CGAF_DEV_PATH . self::getConfig("assetpath", 'assets'));
		}
		//ppd( self::getInternalStorage('log',false) . DS . 'cgaf.error.log' );
		$errors = self::getConfigs('errors' . (CGAF_DEBUG ? '.debug' : ''), array());
		foreach ($errors as $k => $v) {
			if ($k === 'debug')
				continue;
			switch ($k) {
			case 'log_errors':
				if ($v === null) {
					$v = true;
				}
				break;
			case 'error_log':
				if ($v === null) {
					$errors[$k] = self::getInternalStorage('log', false) . DS . 'cgaf.error.log';
				}
				break;
			}
		}
		if (CGAF_DEBUG) {
			self::$_configuration->setconfig('errors', $errors);
		}
		define('CGAF_LIB_PATH', self::getConfig('cgaf.libspath', CGAF_PATH . DS . 'Libs') . DS);
		self::addClassPath('Libs', CGAF_LIB_PATH, false);
		self::addClassPath('system', CGAF_LIB_PATH, false);
		if (!defined("CGAF_VENDOR_PATH")) {
			define("CGAF_VENDOR_PATH", self::getConfig('cgaf.vendorpath', CGAF_PATH . 'vendor' . DS), false);
		}
		self::addClassPath('Vendor', CGAF_VENDOR_PATH);
		if (!defined("CGAF_LIVE_PATH")) {
			define("CGAF_LIVE_PATH", CGAF_PATH);
		}
		self::addAlowedLiveAssetPath(CGAF_LIVE_PATH . self::getConfig("livedatapath", "assets"));
		if (CGAF_DEBUG) {
			self::addAlowedLiveAssetPath(CGAF_DEV_PATH . self::getConfig("livedatapath", "assets"));
		}
		self::$_initialized = true;
		return true;
	}
	static function getTempPath() {
		return self::getConfig("temp.path", CGAF_PATH . 'tmp' . DS);
	}
	private static function offlineRedirect($code) {
		//TODO move to response
		if (!System::isConsole()) {
			Response::redirect(BASE_URL . 'offline.php?code=1');
		} else {
			Response::writeln(__('offline.' . $code, 'Offline : ' . $code));
			CGAF::doExit();
		}
	}
	public static function onSessionEvent($event, $sid = null) {
		if (!($event instanceof SessionEvent)) {
			throw new SystemException('invalid event');
		}
		$sender = $event->sender;
		$q = new DBQuery(self::getDBConnection());
		$sid = $sid ? $sid : $sender->getId();
		self::Using('Models.session');
		$sess = new \System\Models\SessionModel();
		switch ($event->type) {
		case SessionEvent::SESSION_GC:
		case SessionEvent::SESSION_STARTED:
			$lifetime = $sender->getConfig('gc_maxlifetime');
			$past = time() - $lifetime;
			$uid = self::getACL()->getUserId();
			if ($event->type == SessionEvent::SESSION_STARTED) {
				$q->clear();
				$q->addSQL('SELECT * from #__session  where session_id=' . $q->quote($sid));
				$o = $sess->load($sid);
				if (!$o || !$o->session_id) {
					$q->clear();
					$q->addTable('session');
					$q->addInsert('user_id', $uid);
					$q->addInsert('session_id', $sid);
					$q->addInsert('client_id', $_SERVER['REMOTE_ADDR']);
					$q->addInsert('last_access', $q->toDate());
					$q->exec();
				} else {
					$q->clear();
					$q->addTable('session');
					$q->Where('session_id=' . $q->quote($sid));
					$q->Update('last_access', $q->toDate());
					$q->update('user_id', $uid);
					$q->exec();
				}
			}
			$q->clear()->addTable('session');
			$q->where('last_access < ' . $q->quote($q->toDate($past)));
			$q->delete()->exec();
			break;
		case SessionEvent::DESTROY:
			$q->clear()->addTable('session')->where('session_id=' . $q->quote($sid))->delete();
			$q->exec();
		}
	}
	private static function handleAssetNotFound() {
		$f = $_REQUEST["__url"];
		$ext = Utils::getFileExt($f, false);
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
			$alt = SITE_PATH . "assets/images/alts/empty." . $ext;
			break;
		}
		if ($alt && is_file($alt)) {
			return Streamer::render($alt);
		}
		throw new AssetException("asset not found %s", $f);
	}
	static function Run($appName = null, $installMode = false) {
		if (!self::Initialize()) {
			die("unable to initialize framework");
		}
		if (!self::isInstalled() && !$installMode) {
			Response::redirect(BASE_URL . 'Applications/Install/');
			//return self::offlineRedirect(0);
		}
		if (self::getConfig('offline')) {
			self::offlineRedirect(1);
		}
		AppManager::initialize();
		self::exitIfNotModified();
		//TODO moved to application
		if (isset($_REQUEST["__url"]) && String::BeginWith($_REQUEST["__url"], "assets/")) {
			return self::handleAssetNotFound();
		}
		$retval = null;
		Session::getInstance()->addEventListener('*', array(
						'CGAF',
						'onSessionEvent'));
		if (is_object($appName) && $appName instanceof IApplication) {
			AppManager::setActiveApp($appName);
			$instance = $appName;
			if (!$instance->Initialize()) {
				$instance = null;
			}
		} else {
			$instance = null;
			$path = Request::get('__app');
			if ($path) {
				$instance = AppManager::getInstanceByPath($path);
				AppManager::setActiveApp($appName);
				$instance = $appName;
			}
			$appId = Request::get('__appId');
			if (!$instance && $appId) {
				try {
					if (AppManager::isAppIdInstalled($appId)) {
						Session::set('__appId', $appId);
						$appName = $appId;
					} else {
						throw new SystemException('Application ' . $appId . 'not installed');
					}
				} catch (Exception $e) {
				}
			}
			$cgaf = Request::get('__cgaf', null, true);
			switch (strtolower($cgaf)) {
			case 'reset':
				if (self::isDebugMode()) {
					//perform acl to destroy
					self::getACL();
					Session::destroy();
					Response::Redirect('/?__t=' . time());
					return;
				}
				break;
			case '__switchapp':
				$appId = Request::get('id');
				if ($appId && AppManager::isAppIdInstalled($appId)) {
					Session::set('__appId', $appId);
					Response::Redirect('/');
					return;
				}
				break;
			case '_installapp':
				if (CGAF_DEBUG || ACLHelper::isInrole(ACLHelper::DEV_GROUP)) {
					$id = Request::get('id');
					if ($id) {
						$appId = AppManager::install($id);
						Response::Redirect(URLHelper::addParam(BASE_URL, array(
								'__appId' => $appId)));
						return;
					}
				}
				break;
			default:
				try {
					$instance = AppManager::getInstance($appName);
				} catch (Exception $ex) {
					$instance = AppManager::getInstance('__cgaf');
				}
				break;
			}
		}
		if (!$instance) {
			die("Application Instance not found/Access Denied");
		}
		//ppd($instance);
		Response::StartBuffer();
		$retval = $instance->Run();
		if ($retval) {
			Response::write($retval);
			Response::EndBuffer(true);
		}
		return true;
	}
	public static function addNamespaceSearchPathx($prefix, $path, $normalize = true) {
		$path = Utils::ToDirectory($path . DS);
		$prefix = $prefix ? $prefix : "__common";
		if (!isset(self::$_searchPath[$prefix])) {
			self::$_searchPath[$prefix] = array();
		}
		if (!is_array($path)) {
			if (!is_dir($path)) {
				return;
			}
			$path = array(
					$path);
		}
		$spath = array();
		if ($normalize) {
			foreach ($path as $v) {
				self::addStandardSearchPath($prefix, $v . 'classes' . DS, false);
				self::addStandardSearchPath($prefix, $v . DS, false);
			}
		} else {
			$spath = $path;
		}
		$paths = array();
		foreach ($spath as $path) {
			if (!in_array($path, self::$_searchPath[$prefix])) {
				$paths[] = $path;
			}
		}
		self::$_searchPath[$prefix] = array_merge($spath, self::$_searchPath[$prefix]);
	}
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $prefix
	 * @param unknown_type $path
	 * @param unknown_type $normalize
	 * @deprecated
	 */
	public static function addStandardSearchPathx($prefix, $path, $normalize = true) {
		return self::addNamespaceSearchPath($prefix, $path, $normalize);
	}
	public static function addClassPath($nsName, $path) {
		$path = str_replace("/", DS, $path);
		$path = str_replace(DS . DS, DS, $path);
		$nsName = strtolower($nsName);
		if (!isset(self::$_classPath[$nsName])) {
			self::$_classPath[$nsName] = array();
		}
		$old = self::$_classPath[$nsName];
		if (is_string($path)) {
			$path = array(
					$path);
		}
		foreach ($path as $p) {
			if (!in_array($p, $old)) {
				$old[] = $p;
			}
		}
		self::$_classPath[$nsName] = array_reverse($old, false);
	}
	public static function getClassPath($nsname = null) {
		if ($nsname) {
			$nsname = strtolower($nsname);
			return isset(self::$_classPath[$nsname]) ? self::$_classPath[$nsname] : null;
		}
		return self::$_classPath;
	}
	protected static function toNS($f) {
		$cpath = str_ireplace(DS . DS, DS, CGAF_PATH . DS . "System");
		$f = str_ireplace(DS . DS, DS, $f);
		foreach (self::$_classPath as $n => $v) {
			$f = str_replace("$v.", "$n.", $f);
			$f = str_replace("$v", "$n.", $f);
		}
		$f = str_ireplace($cpath, "System.", $f);
		$f = str_ireplace(CGAF_CLASS_EXT, "", $f);
		$f = str_ireplace("/", DS, $f);
		$f = str_ireplace(DS, ".", $f);
		$f = str_ireplace("..", ".", $f);
		return $f;
	}
	private static function nsNormalize($ns, $p) {
		if (!class_exists("Utils", false)) {
			return $ns;
		}
		$p = Utils::ToDirectory($p);
		$rns = self::$_classPath[$ns];
		$p = str_replace($rns, "", $p);
		//$nns = substr ( $ns, 0, strpos ( $ns, "." ) );
		return $p;
	}
	private static function nsDebug($cat, $ns, $f) {
		self::$_nsDebug[$ns][$cat][] = $f;
	}
	private static function UsingDir($fname) {
		$dir = opendir($fname);
		while ($d = readdir($dir)) {
			if (substr($d, 0, 1) !== "." && is_file($fname . DS . $d)) {
				$ext2 = substr($d, strlen($d) - 3);
				if (is_file($fname . DS . $d)) {
					self::Using($fname . DS . $d, false);
				}
			}
		}
		closedir($dir);
		return true;
	}
	private static function checkUsing($fname, $check = true) {
		$namespace = $fname;
		$fname = str_ireplace(".", DS, $fname);
		if (substr($fname, strlen($fname) - 4, 1) == '.') {
			$ext = substr($fname, strlen($fname) - 3);
		} else {
			$ext = CGAF_CLASS_EXT;
		}
		if (is_file($fname . $ext)) {
			return self::Using($fname . $ext, false);
		} elseif (is_file($fname . CGAF_CLASS_EXT)) {
			return self::Using($fname . CGAF_CLASS_EXT, false);
		} elseif (is_dir($fname)) {
			return self::UsingDir($fname);
		} elseif ($check) {
			$spath = array(
					CGAF_PATH . "System",
					CGAF_PATH);
			if (class_exists("AppManager", false)) {
				if (AppManager::isAppStarted()) {
					$path = AppManager::getInstance()->getClassPath();
					$spath = array_merge($spath, array(
							$path));
				}
			}
			foreach ($spath as $path) {
				if (self::checkUsing($path . DS . $fname . $ext, false)) {
					return TRUE;
				} elseif (self::checkUsing($path . DS . $fname, false)) {
					return true;
				}
			}
			foreach (self::$_classPath as $k => $path) {
				$nfname = Utils::ToDirectory($path . DS . $fname);
				//pp($nfname);
				if (self::checkUsing($nfname, false)) {
					return true;
				} elseif (String::BeginWith($namespace, $k, true)) {
					$tfname = Utils::ToDirectory($path . DS . substr(str_replace('.', DS, $namespace), strlen($k) + 1)) . CGAF_CLASS_EXT;
					if (self::Using($tfname, false)) {
						return true;
					}
				}
			}
			if (class_exists("AppManager", false)) {
				if (AppManager::isAppStarted()) {
					return AppManager::getInstance()->unhandledNameSpace($namespace);
				}
			}
		}
		return false;
	}
	private static function _toNS($ns) {
		//$ns = strtolower ( $ns );
		$lpos = strrpos($ns, '.', -4);
		$ext = substr($ns, $lpos);
		foreach (self::$_classPath as $k => $v) {
			foreach ($v as $p) {
				if (substr($ns, 0, strlen($p)) === $p) {
					$ns = $k . '.' . substr($ns, strlen($p));
				}
			}
		}
		if ($ext === CGAF_CLASS_EXT) {
			$ns = substr($ns, 0, strlen($ns) - strlen($ext));
		}
		$ns = str_replace(CGAF_PATH, '', $ns);
		$ns = str_replace(DS, '.', $ns);
		return $ns;
	}
	private static function _getFileOfNS($ns, $debug = false) {
		$retval = null;
		if (strpos($ns, '.') !== false) {
			$first = substr($ns, 0, strpos($ns, '.'));
			$spath = self::getClassPath($first);
			$fns = substr($ns, strlen($first) + 1);
		} else {
			$fns = $ns;
			$spath = self::getClassPath('System');
		}
		$fns = str_replace('.', DS, $fns);
		if ($spath) {
			foreach ($spath as $path) {
				$fname = Utils::ToDirectory($path . DS . $fns);
				if (is_file($fname . CGAF_CLASS_EXT)) {
					$retval = Utils::arrayMerge($retval, $fname . CGAF_CLASS_EXT, true);
				} elseif (is_file($path . DS . strtolower($fns) . CGAF_CLASS_EXT)) {
					$retval = Utils::arrayMerge($retval, $path . DS . strtolower($fns) . CGAF_CLASS_EXT, true);
				} elseif (is_dir($fname)) {
					$files = array();
					$retval = Utils::arrayMerge($retval, \Utils::getDirFiles($fname . DS, $fname . DS, FALSE, "/\\" . CGAF_CLASS_EXT . "/i"), true);
				}
			}
		} else {
			Logger::Warning('unhandled namespace parent of ' . $first . ' at ' . $ns);
		}
		return $retval;
	}
	public static function Using($namespace = null, $throw = true) {
		if ($namespace === null) {
			return self::$_namespaces;
		}
		if (is_array($namespace)) {
			$retval = array();
			foreach ($namespace as $k => $v) {
				$retval[$k] = self::Using($v);
			}
			return $retval;
		}
		$star = false;
		if (substr($namespace, strlen($namespace) - 1) === '*') {
			$namespace = substr($namespace, 0, strlen($namespace) - 2);
			$star = true;
		}
		$nsnormal = self::_toNS($namespace);
		if (is_file($namespace)) {
			if (!isset(self::$_namespaces[$nsnormal])) {
				self::$_namespaces[$nsnormal] = array();
			}
			if (in_array($namespace, self::$_namespaces[$nsnormal]))
				return true;
			self::$_namespaces[$nsnormal][] = $namespace;
			require $namespace;
			return true;
		}
		$f = self::_getFileOfNS($nsnormal, $star);
		if ($f && is_array($f)) {
			foreach ($f as $v) {
				self::Using($v);
			}
			return true;
		} elseif ($f) {
			return self::Using($f);
		}
		if ($throw) {
			/*if (CGAF_DEBUG) {
			    pp($namespace);
			    pp($f);
			    ppd(self::getClassPath());
			}*/
			throw new System\Exceptions\SystemException($namespace);
		}
		return false;
	}
	/**
	 *
	 * @return IACL
	 */
	public static function getACL() {
		if (self::$_acl == null) {
			self::$_acl = ACLHelper::getACLInstance("db", null);
		}
		return self::$_acl;
	}
	/**
	 *
	 * Enter description here ...
	 * @return System\Cache\Engine\ICacheEngine
	 */
	public static function getInternalCacheManager($app = false) {
		if (self::$_internalCache == null) {
			self::$_internalCache = \System\Cache\CacheFactory::getInstance();
			self::$_internalCache->setCachePath(self::getInternalStorage('.cache', false, true));
		}
		return self::$_internalCache;
	}
	/**
	 *
	 * Enter description here ...
	 * @return System\Cache\Engine\ICacheEngine
	 */
	public static function getCacheManager() {
		if (self::$_cacheManager == null) {
			self::$_cacheManager = \System\Cache\CacheFactory::getInstance();
		}
		return self::$_cacheManager;
	}
	public static function isAllow($o, $group) {
		return self::getACL()->isAllow($o, $group);
	}
	public static function assetToLive($asset) {
		if (!self::isAllowAssetToLive($asset)) {
			return null;
		}
		if (Utils::isLive($asset)) {
			return Utils::PathToLive($asset);
		}
		return self::pathToLive($asset);
	}
	protected static function pathToLive($path) {
		if (String::BeginWith($path, self::$_allowedLivePath, true)) {
			$path = String::Replace(self::$_allowedLivePath, BASE_URL . 'assets', $path);
			$cpath = CGAF_SHARED_PATH . self::getConfig("assetpath", 'assets');
			return $path;
		}
		/*if (String::BeginWith($path, $cpath)) {
		    ppd(CGAF_SHARED_PATH);
		}

		pp($path);
		ppd(self::$_allowedLivePath);*/
		throw new Exception("x");
	}
	public static function addAlowedLiveAssetPath($path) {
		if (!in_array($path, self::$_allowedLivePath)) {
			self::$_allowedLivePath[] = $path;
		}
	}
	public static function isAllowAssetToLive($asset) {
		if (String::BeginWith($asset, 'https:') || String::BeginWith($asset, 'http:') || String::BeginWith($asset, 'ftp:')) {
			return $asset;
		}
		if (!String::BeginWith($asset, self::$_allowedLivePath)) {
			if (CGAF_DEBUG) {
				pp($asset);
				pp(self::$_allowedLivePath);
			}
			return false;
		}
		return true;
	}
	public static function isAllowFile($asset, $access = NULL) {
		$allow = array();
		$asset = Utils::ToDirectory($asset);
		if (AppManager::isAppStarted()) {
			$allow[] = AppManager::getInstance()->getLivePath();
			$allow[] = AppManager::getInstance()->getTemporaryPath();
		}
		if (String::EndWith($asset, array(
				'.manifest',
				'.min.js',
				'.min.css'), true)) {
			return true;
		}
		$allow[] = self::getTempPath();
		if (CGAF_DEBUG) {
			$allow[] = Utils::ToDirectory(SITE_PATH . 'assets/compiled/');
		}
		if (String::BeginWith($asset, $allow)) {
			return true;
		}
		if (String::Contains($asset, array(
				'.cache'))) {
			return true;
		}
		return false;
	}
	public static function isShutdown() {
		return self::$_shutdown;
	}
	public static function RegisterAutoLoad($func) {
		if (!in_array($func, self::$_autoLoadCallBack)) {
			self::$_autoLoadCallBack[] = $func;
		}
	}
	public static function AddNamespaceClass($prefix, $ns) {
		self::$_nsClass[$prefix] = $ns;
	}
	private static function _getClassInstance($className, $suffix, $args, $find = true) {
		$cname = array();
		if (class_exists('AppManager', false) && AppManager::isAppStarted()) {
			$cname[] = AppManager::getInstance()->getAppName() . $className . $suffix;
		}
		$suffix = strtolower($suffix);
		if (CGAF_CLASS_PREFIX) {
			$cname[] = CGAF_CLASS_PREFIX . $className . $suffix;
		}
		$cname[] = $className . $suffix;
		foreach ($cname as $c) {
			if (class_exists($c, false)) {
				return new $c($args);
			}
		}
	}
	public static function getClassNameFor($classname, $namespace, $useApp = true) {
		if ($useApp && AppManager::isAppStarted()) {
			return AppManager::getInstance()->getClassNameFor($className, $namespace);
		}
		$search = array(
				$namespace . '\\' . $classname,
				$classname);
		foreach ($search as $s) {
			if (class_exists($s, false)) {
				return $s;
			}
			$s = '\\' . $s;
			if (class_exists($s, false)) {
				return $s;
			}
		}
		return null;
	}
	public static function getClassInstance($className, $suffix, $args, $prefix = null) {
		$ci = self::_getClassInstance($className, $suffix, $args);
		if (!$ci) {
			//pp($suffix);
			$cpath = self::getClassPath($suffix);
			if (!$cpath) {
				return false;
			}
			foreach ($cpath as $c) {
				$cf = Utils::ToDirectory($c . DS . strtolower($className) . '.class' . CGAF_CLASS_EXT);
				self::Using($cf, false);
				$ci = self::_getClassInstance($className, $suffix, $args);
				if ($ci) {
					return $ci;
				}
				$cf = Utils::ToDirectory($c . DS . strtolower($className) . CGAF_CLASS_EXT);
				self::Using($cf, false);
				$ci = self::_getClassInstance($className, $suffix, $args);
				if ($ci) {
					return $ci;
				}
			}
		}
		return $ci;
	}
	private static function _loadNS($ns, $expectedClass) {
	}
	public static function LoadClass($className, $throw = true) {
		$className = str_replace(array(
				'/',
				'\\'), DS, $className);
		if (substr($className, 0, 1) === DS) {
			$className = substr($className, 1);
		}
		$namespaces = explode(DIRECTORY_SEPARATOR, $className);
		unset($namespaces[sizeof($namespaces) - 1]); // the last item is the classname
		$clname = $className;
		$oricpath = null;
		$nspath = array();
		if (count($namespaces)) {
			$fns = $namespaces[0];
			$classpart = explode(DS, $className);
			if ($classpart[0] === 'System') {
				unset($classpart[0]);
				unset($namespaces[0]);
				if (count($namespaces)) {
					$akey = array_keys($namespaces);
					\Utils::arrayMerge($nspath, self::getClassPath($namespaces[$akey[0]]));
				}
			}
			$oricpath = str_replace('//', '', implode(DS, $classpart));
			$cname = strtolower(array_pop($classpart));
			//BUG : count and sizeof depend on 0 index ?
			//$classpart [count ( $classpart )] = strtolower ( $classpart [count ( $classpart )] );
			$clname = implode(DS, $classpart) . DS . $cname;
		} else {
			$fns = 'System';
			$oricpath = $clname;
			$clname = strtolower($clname);
		}
		if (!$fns) {
			$fns = 'System';
		}
		$nspath = array_merge($nspath, self::getClassPath($fns));
		$current = "";
		$fdebug = array();
		foreach ($nspath as $p) {
			foreach ($namespaces as $namepart) {
				$current .= '\\' . $namepart;
				if (in_array($current, self::$loadedNamespaces))
					continue;
				self::$loadedNamespaces[] = $current;
				$fnload = $p . $current . DS . "__init.php";
				if (file_exists($fnload))
					require($fnload);
				$fnload = $p . $current . DS . $oricpath . DS . "__init.php";
				if (file_exists($fnload))
					require($fnload);
			}
			if ($oricpath && ($clname !== $oricpath)) {
				$fnload = $p . $oricpath . DS . "__init.php";
				if (file_exists($fnload))
					require($fnload);
				$fname = $p . $oricpath . '.php';
				$fdebug[] = $fname;
				if (is_file($fname)) {
					$fdebug[] = $fname;
					self::Using($fname);
					break;
				}
			}
			$fname = $p . $clname . '.php';
			$fdebug[] = $fname;
			if (is_file($fname)) {
				self::Using($fname);
				break;
			}
		}
		if (!class_exists($className, false)) {
			foreach (self::$_autoLoadCallBack as $func) {
				if (call_user_func_array($func, array(
						$className))) {
					break;
				}
			}
		}
		$className = str_replace(array(
				'/',
				'\\'), "\\", $className);
		// return true if class is loaded
		if (class_exists($className, false) || interface_exists($className, false)) {
			return false;
		}
		\Logger::Warning('Unable to load class ' . $className);
		if (CGAF_DEBUG && $throw) {
			pp(get_declared_classes());
			pp(get_declared_interfaces());
			pp($namespaces);
			pp($nspath);
			ppd($fdebug);
		}
		return false;
	}
	public static function loadLibs($libName) {
		$libName = str_replace("/", ".", $libName);
		$libName = str_replace(DS, ".", $libName);
		if (!self::Using('Libs.' . $libName, true)) {
			self::Using("Libs.$libName.$libName");
		}
	}
	/**
	 *
	 * Enter description here ...
	 * @return System\DB\DBConnection
	 */
	public static function getDBConnection() {
		if (self::$_dbConnection == null) {
			self::using("System.DB");
			$args = self::getConfigs("cgaf.db");
			if (!$args) {
				$args = self::getConfigs('db');
			}
			self::$_dbConnection = DB::Connect($args);
			self::$_dbConnection->setThrowOnError(true);
		}
		return self::$_dbConnection;
	}
	/**
	 *
	 * @param $o
	 * @return IConnector
	 */
	public static function getConnector($o = null) {
		$q = new DBQuery(self::getDBConnection());
		if ($o) {
			return $q->addTable($o);
		}
		return $q;
	}
	/**
	 * @return TLocale
	 */
	public static function getLocale() {
		if (AppManager::isAppStarted()) {
			return AppManager::getInstance()->getLocale();
		}
		if (self::$_locale == null) {
			self::$_locale = new Locale(self::getConfig("locale.locale.default", "en"), CGAF_PATH . DS . "locale");
		}
		return self::$_locale;
	}
	public static function _($msg, $def = null, $locale = null) {
		if (class_exists('AppManager', false)) {
			if (AppManager::getActiveApp() != null) {
				return AppManager::getInstance()->getLocale()->_($msg, $def, null, $locale);
			}
		}
		return self::getLocale()->_($msg, $def, null, $locale);
	}
	public static function getInternalStorage($path = null, $checkApp = true, $create = false) {
		static $istorage;
		if (!$istorage) {
			$istorage = Utils::ToDirectory(CGAF_PATH . DS . self::getConfig('app.internalstorage', 'protected') . DS);
		}
		$retval = null;
		if ($checkApp && AppManager::isAppStarted()) {
			return AppManager::getInstance()->getInternalStorage($path, $create);
		} else {
			if ($create) {
				\Utils::makeDir($istorage . $path);
			}
			return $istorage . $path;
		}
	}
}
spl_autoload_register('CGAF::LoadClass');
include 'cgaf.func.php';
}
?>
