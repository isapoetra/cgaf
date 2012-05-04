<?php
namespace System\Applications;
use System\DB\DBUtil;

use System\Documents\ODF\Dio\Style\DefaultStyle;
use System\Configurations\UserConfiguration;
use System\Locale\Locale;
use System\DB\DBQuery;
use System\ACL\ACLHelper;
use System\Events\AuthEvent;
use CGAF, Utils, Request, Response, Logger;
use System\Assets\AssetHelper;
use System\Session\Session;
use System\Configurations\Configuration;
use System\Collections\ClientAssetCollections;
use URLHelper;
use System\DB\DB;
use System\Exceptions\SystemException;
use System\Collections\Items\AssetItem;
use System\Assets\AssetBuilder;
use AppManager;

/**
 * Enter description here .
 * ..
 *
 * @author Iwan Sapoetra @ Jun 18, 2011
 */
abstract class AbstractApplication extends \BaseObject implements IApplication {
	private $_appPath;
	private $_initialized;
	private $_appName;
	/**
	 * @var \System\IAuthentificator
	 */
	private $_authentificatorInstance;
	private $_vars = array();
	private $_locale;
	/**
	 * @var \System\Cache\Engine\ICacheEngine
	 */
	private $_cacheManager;
	private $_assetPath = array();
	/**
	 * @var \System\Configurations\IConfiguration
	 */
	private $_userConfig;
	/**
	 * @var \System\DB\IDBConnection
	 */
	private $_dbConnection;
	/**
	 * @var \System\Configurations\IConfiguration
	 */
	private $_configs;
	/**
	 * @var \System\ACL\IACL
	 */
	private $_acl;
	/**
	 * Enter description here .
	 * ..
	 *
	 * @var string
	 */
	protected $_lastError;
	protected $_template = null;
	private $_parent;
	/**
	 * @var array cached by type

	 */
	protected $_assetCache = array();
	private $_assetURL;
	protected $_appInfo;
	protected $_clientAssets;
	protected $_cachedAssets = array();
	/**
	 * @var \System\Cache\Engine\ICacheEngine
	 */
	private $_internalCache;
	protected $_configFile;
	/**
	 * @param $appPath string
	 * @param $appName string
	 */
	function __construct($appPath, $appName) {
		$this->_clientAssets = new ClientAssetCollections ($this);
		if ($appName != null) {
			CGAF::addClassPath($appName, $appPath);
			CGAF::addClassPath($appName . 'Class', $appPath . DS . "classes");
		}
		if (substr($appPath, strlen($appPath) - 1) !== DS) {
			$appPath = $appPath . DS;
		}
		$this->_appPath = $appPath;
		$this->_appName = $appName;
		$this->_isAuthentificated = Session::get("__auth", false) && is_object(Session::get("__logonInfo", null));
		$cf = $this->_appPath . DS . "config.php";
		$this->_configs = new Configuration ();
		$this->_configFile = $this->_configFile ? $this->_configFile : $this->_appPath . DS . 'config';

		$this->_configs->loadFile($this->_configFile, false);
		if (!$this->getConfig('app.internalstorage')) {
			$path = $this->getAppPath() . '/protected/';
			$this->setConfig('app.internalstorage', $path);
		}
	}
	function __destruct() {
		if ($this->getConfig('app.autosaveConfig',false)) {
			$this->_configs->Save();
		}
		parent::__destruct();
	}
	/**
	 * @return \System\Collections\ClientAssetCollections
	 */
	public function getClientAssets() {
		return $this->_clientAssets;
	}

	/**
	 * @return \System\Configurations\Configuration|\System\Configurations\IConfiguration
	 */
	public function getConfigInstance() {
		return $this->_configs;
	}

	/**
	 * @param mixed  $assetName
	 * @param null   $group
	 *
	 * @return null|AbstractApplication
	 */
	public function addClientAsset($assetName, $group = null) {
		if (!$assetName) {
			return null;
		}
		if (is_array($assetName) || is_object($assetName)) {
			if (is_object($assetName) && $assetName instanceof AssetItem) {
				$this->_clientAssets->add($assetName, $group);
				return $this;
			}
			foreach ($assetName as $k => $v) {
				if (is_numeric($k)) {
					$this->addClientAsset($v);
				} else {
					$this->addClientAsset($assetName, $group);
				}
			}
			return $this;
		}

		$this->_clientAssets->add($assetName, $group);
		if (CGAF_DEBUG) {
			$this->_clientAssets->add(\Utils::changeFileName($assetName,\Utils::getFileName($assetName,false).'_debug'), $group);
		}
		return $this;
	}

	/**
	 * @return \System\Collections\ClientAssetCollections
	 */
	function getClientAsset() {
		return $this->_clientAssets;
	}

	/**
	 * @param mixed $var
	 * @param null  $val
	 *
	 * @return array
	 */
	public function Assign($var, $val = null) {
		return $this->_vars [$var] = $val;
	}

	/**
	 * @param null $id
	 *
	 * @return array|null
	 */
	function getVars($id = null) {
		return $id ? (isset ($this->_vars [$id]) ? $this->_vars [$id] : null) : $this->_vars;
	}

	protected function checkInstall() {
	}

	protected function unserialize($o) {
		return unserialize(base64_decode($o));
	}

	protected function serialize($o) {
		return base64_encode(serialize($o));
	}

	/**
	 * @return System\Locale\Locale
	 */
	function getLocale() {
		if ($this->_locale == null) {
			$this->_locale = new Locale ($this);
		}
		return $this->_locale;
	}

	function getCurrentLocaleId() {
		return $this
		->getLocale()
		->getLocale();
	}
	/**
	 * @return \System\Configurations\Configuration
	 */
	protected function getInstallConfig() {
		static $_config;
		if (!$_config) {
			$path = $this->getInternalStorage('install') . 'config.php';
			$_config = new Configuration(null,false);
			if (is_file($path)) {
				$_config->loadFile($path);
			}
		}
		return $_config;
	}

	/**
	 * @return bool
	 */
	function Install() {
		//$appName = $this->getAppName();
		$q = new DBQuery (CGAF::getDBConnection());
		$qid = $q->quote($this->getAppId());
		$config = $this->getInstallConfig();
		if ($this->getConfig('install.defaultgrant', true)) {
			$q->exec('insert into #__roles value(0,' . $qid . ',' . $q->quote('guest') . ',1,-1)');
			$q->exec('insert into #__roles value(1,' . $qid . ',' . $q->quote('public') . ',1,-1)');
			$q->exec('insert into #__roles value(2,' . $qid . ',' . $q->quote('developers') . ',1,-1)');
			$q->exec('insert into #__roles value(3,' . $qid . ',' . $q->quote('administrators') . ',1,-1)');
			// Privs
			$q->exec('insert into #__user_roles values(0,' . $qid . ',-1,1)');
			$q->exec('insert into #__user_roles values(1,' . $qid . ',2,1)');
			$q->exec('insert into #__user_roles values(2,' . $qid . ',1,1)');
			$q->exec('insert into #__user_roles values(3,' . $qid . ',1,1)');
			$q->exec('insert into #__role_privs values(1,' . $qid . ',' . $qid . ',' .  $q->quote('app') . ',1)');
			$q->exec('insert into #__role_privs values(2,' . $qid . ',' . $qid . ',' . $q->quote('app') . ',64)');
			$q->exec('insert into #__role_privs values(3,' . $qid . ',' . $qid . ',' . $q->quote('app') . ',16)');
			$q->exec('insert into #__role_privs values(1,\'__cgaf\',' . $qid . ',' . $q->quote('app') . ',1)');
		}
		$q->setThrowOnError(true);
		if ($config->getConfig('guest')) {
			$q->exec(
					'insert into #__role_privs value(0,' . $q->quote('__cgaf') . ',' . $qid . ',' . $q->quote('app') . ',1)'
			);
		}
		$path = $this->getInternalStorage('install/db/common/');
		$cf = $path . 'cgaf.sql';
		if (is_file($cf)) {
			$q->loadSQLFile($cf);
			$q->exec();
		}
		$cf = $path . 'app.sql';
		if (is_file($cf)) {
			$dbc = CGAF::getConfigs('db');
			$c1 = $this->getConfigs('db');
			\Utils::arrayMerge($c1, $dbc);
			\Utils::arrayMerge($c1, $this->getConfigs('db'));
			$con = DB::Connect($c1);
			$q = new DBQuery ($con);
			if ($q->loadSQLFile($cf)) {
				$q->exec();
			}
		}
		$this->setConfig('app.installed', true);
		return true;
	}

	/**
	 * @return bool
	 */
	function Uninstall() {
		$this
		->getInternalCache()
		->clear(false);
		return true;
	}

	/**
	 * @return bool
	 */
	function LogOut() {
		$authentificator = $this->getAuthentificator();
		$retval = $authentificator->logout();
		if ($retval) {
			$this->dispatchEvent(new AuthEvent($this, AuthEvent::LOGOUT));
		}
		return $retval;
	}

	/**
	 * @param null $provider
	 *
	 * @return \System\IAuthentificator
	 */
	function getAuthentificator($provider = null) {
		if ($provider || $this->_authentificatorInstance === null) {
			$class = '\\System\\Auth\\' . ($provider ? : $this->getConfig("authentificator.class", "Local"));
			$instance = new $class ($this);
			if (!$this->_authentificatorInstance) {
				$this->_authentificatorInstance = $instance;
			}
		}
		return $this->_authentificatorInstance;
	}

	function setAppInfo($appInfo) {
		if ($this->_appInfo == null) {
			$info = new \stdClass ();
			$info = Utils::bindToObject($info, $appInfo, true);
			$this->_appInfo = $info;
		}
	}

	/**
	 * @return mixed
	 */
	function getAppInfo() {
		if (!$this->_appInfo) {
			$this->_appInfo = AppManager::getAppInfo($this);
		}
		return $this->_appInfo;
	}

	function getAppId() {
		$info = null;
		if ($this->_appInfo) {
			$info = $this->getAppInfo();
		}
		return $info ? $info->app_id : $this->getConfig('app.id');
	}

	function resetToken() {
		$token = md5(uniqid(rand(), true));
		Session::set("__token", $token);
		return $token;
	}

	function getToken() {
		$retval = Session::get("__token");
		if (!$retval) {
			$retval = $this->resetToken();
		}
		return $retval;
	}

	function isValidToken($req = "__token") {
		$st = Session::get('__token');
		$rt = Request::get($req, null, true, 'p');
		return $rt !== null && $st === $rt;
	}

	function Authenticate() {
		Response::forceContentExpires();
		if ($this->isAuthentificated()) {
			return true;
		}
		if (!$this->isValidToken()) {
			$this->resetToken();
			throw new SystemException ('error.invalidtoken');
		}
		$info = $this
		->getAuthentificator(Request::get('_provider'))
		->Authenticate();
		if ($info) {
			$this->resetToken();
		} else {
			$this->setLastError(
					$this
					->getAuthentificator()
					->getLastError()
			);
		}
		if ($this->isAuthentificated()) {
			$this->dispatchEvent(new AuthEvent($this, AuthEvent::LOGIN));
		}
		return $this->isAuthentificated();
	}

	public function handleError(\Exception $ex) {
		Logger::Error("[%s] %s", get_class($ex), $ex->getMessage());
	}

	public function handleModuleNotFound($m) {
		throw new SystemException ("Unbable to find module" . $m);
	}

	public function isAllow($id, $group, $access = 'view') {
		return $this
		->getACL()
		->isAllow($id, $group, $access);
	}

	function isDebugMode() {
		return $this->getConfig('app.debugmode', \CGAF::isDebugMode());
	}

	public function isAllowFile($file, $access = 'view') {
		$ext = Utils::getFileExt($file, false);
		if (in_array(
				$ext, array(
						'manifest'
				)
		)
		) {
			return true;
		}
		return false;
	}

	public function getACL() {
		if ($this->_acl === null) {
			$class = $this->getConfig("acl.handler", "db");
			$this->_acl = ACLHelper::getACLInstance($class, $this);
		}
		return $this->_acl;
	}

	protected function setDBConnection($connection) {
		$this->_dbConnection = $connection;
	}

	function getDBConnection() {
		if ($this->_dbConnection == null) {
			$configs = $this->getConfigs("db",array());
			$cconfigs = \CGAF::getConfiguration()->getConfigs('db');
			$configs = array_merge($cconfigs,$configs);
			$this->_dbConnection = DB::Connect($configs);
		}
		return $this->_dbConnection;
	}

	protected function switchApp($appId) {
		if (CGAF::isAllow($appId, ACLHelper::APP_GROUP) && AppManager::isAppIdInstalled($appId)) {
			Session::set("_appId", $appId);
			return true;
		}
		return false;
	}

	/**
	 * @param      $configName
	 * @param null $default
	 *
	 * @return array|bool|null|void
	 */
	function getConfig($configName, $default = null) {
		$cg = null;
		if ($this->_configs) {
			$cg = $this->_configs->getConfig($configName, null);
		}
		return $cg === null ? CGAF::getConfig($configName, $default) : $cg;
	}

	function setConfig($configname, $value) {
		return $this->_configs->setConfig($configname, $value);
	}

	/**
	 * @param      $config String
	 * @param null $defaults
	 *
	 * @return array|null|\System\Configurations\Configuration|\System\Configurations\IConfiguration|void
	 */
	function getConfigs($config, $defaults = null) {
		if ($config === null) {
			return $this->_configs;
		}
		$c1 = $this->_configs->getConfigs($config);
		return $c1 === null ? $defaults : $c1;
	}

	function getAppName() {
		if (!$this->_appName) {
			$info = $this->getAppInfo();
			$this->_appName = $info->app_name;
		}
		return $this->_appName;
	}

	function getAuthInfo() {
		return $this
		->getAuthentificator()
		->getAuthInfo();
	}

	function isInitialized() {
		return $this->_initialized;
	}

	function initSession() {
	}

	/**
	 * @param $event \System\Events\Event
	 */
	function onSessionEvent($event) {
	}

	protected function getDevPath($path = NULL) {
		$ap = $this->getConfig('livedatapath', 'assets');
		return Utils::ToDirectory($this->getAppPath() . "DevFiles/$ap/$path/");
	}

	/*
	 * (non-PHPdoc) @see IApplication::Initialize()
	*/
	function Initialize() {
		if ($this->isInitialized()) {
			return true;
		}
		if (CGAF::isDebugMode() || Session::get("installmode")) {
			$this->checkInstall();
		}
		if ($this->isDebugMode()) {
			CGAF::addAlowedLiveAssetPath($this->getDevPath());
		}
		$this->_initialized = true;
		$this->initSession();
		$this->_cachedAssets = unserialize(
				$this
				->getInternalCache()
				->getContent('assets', 'app')
		);
		$this->_cachedAssets = $this->_cachedAssets ? $this->_cachedAssets : array();
		$this->getACL();
		return $this->_initialized;
	}

	function Shutdown() {
		if ($this->_userConfig) {
			/*
			 * $istore = $this->getInternalStoragePath () . DS . 'userconfig' .
			* DS . ACLHelper::getUserId () . '.config'; Utils::makeDir (
					* dirname ( $istore ) ); file_put_contents ( $istore,
							* $this->serialize ( $this->_userConfig ) );
			*/
		}
		if (\CGAF::isInstalled()) {
			$this
			->getInternalCache()
			->put('assets', $this->_cachedAssets, 'app');
		}
	}

	function getAppPath($full = true) {
		if ($full) {
			return $this->_appPath;
		} else {
			return \Strings::FromLastPos(trim($this->_appPath,' '.DS), DS);
		}
	}

	function getContent($position = null) {
		return $position;
	}

	function getSharedPath() {
		return CGAF_PATH . DS . "Data" . DS . "Shared" . DS;
	}

	/**
	 * @param      $search
	 * @param      $search2
	 * @param null $baseP
	 *
	 * @return array
	 */
	protected function _mergeAssetPath($search, $search2, $baseP = null) {
		$appPath = $this->getAppPath(true);
		$basePx = $this->getConfig("livedatapath", "assets");
		$baseP = $baseP ? $baseP : $basePx;
		$retval = $search;
		if (is_array($search2)) {
			foreach ($search2 as $v) {
				$retval = $this->_mergeAssetPath($retval, $v, $baseP);
			}
			return $retval;
		}
		$r = array();
		$r [] = $baseP . DS . $search2;
		foreach ($r as $v) {
			$f = Utils::ToDirectory($appPath . DS . $v . DS);
			if (!in_array($f, $retval)) {
				$retval [] = $f;
			}
			$f = Utils::ToDirectory(SITE_PATH . DS . $v . DS);
			if (!in_array($f, $retval)) {
				$retval [] = $f;
			}
		}
		return $retval;
	}

	function addAssetPath($prefix, $path) {
		if (!isset ($this->_assetPath [$prefix])) {
			$this->_assetPath [$prefix] = array();
		}
		if (is_array($path)) {
			foreach ($path as $p) {
				$this->_assetPath [$prefix] [] = $p;
			}
		} else {
			$this->_assetPath [$prefix] [] = $path;
		}
	}

	protected abstract function getAssetPath($data, $prefix = null);

	/**
	 * @param      $data
	 * @param null $prefix
	 *
	 * @return array|mixed|null
	 */
	function getAsset($data, $prefix = null) {
		if (is_array($data)) {
			$retval = array();
			foreach ($data as $k => $v) {
				if ($v) {
					$asset = $this->getAsset($v);
					if ($asset) {
						$retval [$k] = $asset;
					}
				}
			}
			return $retval;
		}
		$pr = $prefix ? $prefix : '__common';
		if (isset ($this->_cachedAssets [$data] [$pr])) {
			if (is_file($this->_cachedAssets [$data] [$pr])) {
				return $this->_cachedAssets [$data] [$pr];
			}
		}
		if (!is_string($data) || !$data) {
			return null;
		}
		if (Utils::isLive($data)) {
			return $data;
		}
		if (is_file($data)) {
			return $data;
		}
		$search = $this->getAssetPath($data, $prefix);
		$s = array();
		$ext = Utils::getFileExt($data, false);
		$retval = null;
		foreach ($search as $file) {
			// search for current application path
			$fname = Utils::ToDirectory($file . DS . $data);
			$s [] = $fname;
			if (is_file($fname) || is_dir($fname)) {
				switch ($ext) {
					case 'assets' :
						$retval = AssetBuilder::build($fname);
						break;
					default :
						$retval = $fname;
						break;
				}
				if (is_array($prefix) || is_array($data)) {
					pp($prefix);
					ppd($data);
				}
				if (!isset ($this->_cachedAssets [$data])) {
					$this->_cachedAssets [$data] = array();
				}
				$this->_cachedAssets [$data] [$pr] = $retval;
				return $retval;
			}
		}
		return null;
	}

	/**
	 * @return array|bool|mixed|null
	 * @deprecated
	 */
	function getTemporaryPath() {
		return $this->getLivePath(false);
	}

	function getAssetURL() {
		if (!$this->_assetURL) {
			$this->_assetURL = Utils::PathToLive($this->getLivePath());
		}
		return $this->_assetURL;
	}

	/**
	 * @return string
	 * @deprecated


	 */
	function getCachePath() {
		return $this->getTemporaryPath() . 'cache' . DS;
	}

	/**
	 * @return \System\Cache\Engine\ICacheEngine
	 */
	function getCacheManager() {
		if ($this->_cacheManager == null) {
			$class = "\\System\\Cache\\Engine\\" . $this->getConfig("cache.engine", "Base");
			$this->_cacheManager = new $class ($this);
			$this->_cacheManager->setCachePath($this->getTemporaryPath());
		}
		return $this->_cacheManager;
	}

	public function getInternalStoragePath() {
		return Utils::ToDirectory($this->getConfig("app.internalstorage", $this->getAppPath() . DS . "protected") . DS);
	}

	/**
	 * @return \System\Cache\Engine\ICacheEngine
	 */
	public function getInternalCache() {
		if ($this->_internalCache == null) {
			$class = '\\System\\Cache\\Engine\\' . $this->getConfig("cache.engine", "Base");
			$this->_internalCache = new $class ($this);
			$this->_internalCache->setCachePath($this->getInternalStorage('.cache', true));
		}
		return $this->_internalCache;
	}

	function getResource($o, $prefix, $live = true) {
		if ($live) {
			return $this->getLiveAsset($o, $prefix);
		} else {
			return $this->getAsset($o, $prefix);
		}
	}

	function getLiveAsset($data, $prefix = null, $callback = null) {
		$asset = $this->getAsset($data, $prefix);
		if ($asset !== null) {
			$retval = $this->assetToLive($asset);
			if (!$retval) {
				return null;
			}
			$asset = $retval;
		}
		return $asset;
	}

	/**
	 * @param
	 *          $data
	 * @param
	 *          $prefix
	 * @param
	 *          $callback
	 *
	 * @return array|mixed|null|void
	 * @deprecated use getLiveAsset
	 */
	function getLiveData($data, $prefix = null, $callback = null) {
		return $this->getLiveAsset($data, $prefix, $callback);
	}

	function getLivePath($sessionBased = false) {
		return Utils::ToDirectory(
				SITE_PATH . "assets/applications/" . $this->getAppId() . "/" . ($sessionBased ? session_id() . "/" : "")
		);
	}

	public function isAllowToLive($file) {
		if (Utils::isLive($file)) {
			return true;
		}
		$file = realpath($file);
		$allow = array(
				$this->getAppPath() . 'assets'
		);
		if (\Strings::BeginWith($file, $allow)) {
			return true;
		}
		$ext = Utils::getFileExt($file, FALSE);
		return in_array(
				$ext, array(
						'ico',
						'ttf',
						'js',
						'gif',
						'png',
						'jpg',
						'css',
						'assets'
				)
		);
	}

	protected function getLiveAssetPath($asset, $sessionBased = false) {
		$tmp = null;
		if ($asset) {
			$tmp = str_ireplace($this->getAppPath(true), '', $asset);
		}
		return $this->getLivePath($sessionBased) . $tmp;
	}

	/**
	 * @param $cat
	 * @param $msg
	 * @param $success
	 */
	function Log($cat, $msg, $success) {
		$filename = $this->getInternalStorage('.cache/logs/' . $this->getAppId() . '/', true);
		if (!is_dir($filename)) {
			Utils::makeDir($filename);
		}
		$m = array(
				\DateTime::ATOM,
				$_SERVER ['REMOTE_ADDR']
		);
		$msg = implode(",", $m) . ",$msg";
		$filename .= strtolower($cat) . '-' . ($success ? "success" : "failed") . ".log";
		file_put_contents($filename, $msg, FILE_APPEND | LOCK_EX);
	}

	function isAuthentificated() {
		return $this
		->getAuthentificator()
		->isAuthentificated();
	}

	function Run() {
		$this->initRun();
		// Session::Close();
	}

	protected function initRun() {
		return true;
	}

	/**
	 * @param null $uid
	 *
	 * @return Configuration
	 */
	protected function _UserConfigInstance($uid = null) {
		$cid = ACLHelper::isAllowUID(null);
		$uid = $uid == null ? $cid : $uid;
		if (!$this->_userConfig) {
			$this->_userConfig = new UserConfiguration ($this, $uid, null);
		}
		return $this->_userConfig;
	}

	public function getUserStorage($uid = null) {
		$cid = ACLHelper::isAllowUID(null);
		$uid = $uid == null ? $cid : $uid;
		if ($uid === -1) {
			return null;
		}
		return $this->getInternalStorage('users/' . $uid . DS);
	}

	function setUserConfig($configName, $value = null, $uid = null) {
		$instance = $this->_UserConfigInstance($uid);
		return $instance ? $instance->setConfig($configName, $value) : null;
	}

	function getUserConfigs($config, $uid = null) {
		$instance = $this->_UserConfigInstance($uid);
		return $instance->getConfigs($config);
	}

	function getUserConfig($configName, $def = null, $uid = null) {
		$instance = $this->_UserConfigInstance($uid);
		$r = $instance->getConfig($configName, $this->getConfig('userprivacy.' . $configName, null));
		return $r !== null ? $r : $def;
	}

	public function getInternalStorage($path, $create = false) {
		$iPath = Utils::ToDirectory($this->getConfig("app.internalstorage", CGAF_PATH . '/protected/') . "/" . $path . '/');
		if (is_readable($iPath)) {
			return $iPath;
		} else {
			if ($create) {
				return Utils::makeDir($iPath);
			}
			// Logger::Warning("Unable to find Internal Data $iPath");
			return $iPath;
		}
		//return null;
	}

	public function loadClass($classname) {
		$p = Utils::ToDirectory($this->getAppPath() . '/classes/');
		$s = array(
				$classname . CGAF_CLASS_EXT,
				strtolower($classname) . CGAF_CLASS_EXT
		);
		foreach ($s as $ss) {
			if (is_file($p . $ss)) {
				return CGAF::Using($p . $ss);
			}
		}
		return false;
	}

	function getHasParent() {
		return $this->_parent !== null;
	}

	function getParent() {
		return $this->_parent;
	}

	function setParent($parent) {
		$this->_parent = $parent;
	}

	/**
	 * @param      $path
	 * @param bool $create
	 *
	 * @return array|bool|mixed
	 */
	public function getInternalData($path, $create = false) {
		$iPath = Utils::ToDirectory($this->getConfig("app.internalstorage") . DS . $path . DS);
		if (is_readable($iPath)) {
			return $iPath;
		} else {
			if ($create) {
				return Utils::makeDir($iPath);
			}
			Logger::Warning("Unable to find Internal Data $iPath");
		}
		return $iPath;
	}

	function getClassPath() {
		return $this->getAppPath(true) . DS . $this->getConfig("app.classpath", 'classes');
	}

	function unhandledNameSpace($namespace) {
		// throw new SystemException($namespace.' NOT FOUND');
		return false;
	}

	/**
	 * @return string
	 */
	function getClassPrefix() {
		return $this->getConfig("class.prefix", Utils::toClassName($this->getAppName()));
	}

	public function getClassNameFor($base, $suffix, $ns = null) {
		$appName = $this->getClassPrefix();
		if ($ns [strlen($ns) - 1] === "\\") {
			$ns = substr($ns, 0, strlen($ns) - 1);
		}
		$nssearch = array(
				$ns . '\\' . $appName . $base . $suffix,
				$ns . '\\' . $appName . $base,
				$ns . '\\' . $base,
				$ns . '\\' . $base . $suffix,
				'\\' . $appName . $base . $suffix,
				'\\' . $base . $suffix,
				$suffix ? '' : '\\' . $base
		);
		foreach ($nssearch as $s) {
			if (!$s) {
				continue;
			}
			if (class_exists($s, false)) {
				return $s;
			}
			if (class_exists('\\' . $s, false)) {
				return '\\' . $s;
			}
		}
		return null;
	}
	function reset() {
		if ($this->getConfig('app.allowreset',$this->isDebugMode())) {
			$this->getACL()->clearCache();
		}
	}
	public function isInstalled() {
		return $this->getConfig('app.installed',false) === true;
	}
	abstract function assetToLive($asset);
	function getAssetCachePath() {
		return ASSET_PATH . '.cache/' . $this->getAppId() . '/assets/';
	}
	function dumpDB($configs =null) {
		$configs = \Utils::arrayMerge($configs, array(
				'includecgaf'=>true
		));

		$db =  $this->getDBConnection();
		$lists = $db->getTableList();
		$dir =  $this->getInternalStorage('install/db/common/');
		\Utils::makeDir($dir);
		while($tbl = $lists->next()) {
			$fname =$dir.$tbl->table_name.'.sql';
			DBUtil::dumpRows($db,$tbl->table_name,$rows,$fname);
		}
		if ($configs['includecgaf']) {
			DBUtil::dumpCGAFAppDB($this->getAppId(), $dir.'cgaf.sql');
		}
		return $dir;
	}
}
?>
