<?php
if (! defined ( "CGAF" ))
die ( "Restricted Access" );

abstract class Application extends Control implements IApplication {
	private $_appPath;
	private $_initialized;
	private $_appName;
	private $_auth;
	private $_vars = array ();
	private $_locale;
	private $_cacheManager;
	private $_assetPath = array ();

	/**
	 *
	 * @var IConfiguration
	 */
	private $_userConfig;
	/**
	 *
	 * @var IDBConnection
	 */
	private $_dbConnection;
	/**
	 *
	 * @var IConfiguration
	 */
	private $_configs;
	private $_acl;
	protected $_lastError;
	protected $_template = null;
	private $_parent;
	protected $_assetCache = array ();
	private $_assetURL;
	protected $_appInfo;
	protected $_clientAssets ;
	private $_clientScripts = array ();

	function __construct($appPath, $appName) {
		global $_configs;
		$this->_clientAssets  = new ClientAssetCollections($this);
		if ($appName != null) {
			CGAF::addClassPath ( $appName, $appPath );
			CGAF::addClassPath ( $appName . 'Class', $appPath . DS . "classes" );
		}
		if (substr ( $appPath, strlen ( $appPath ) - 1 ) !== DS) {
			$appPath = $appPath . DS;
		}
		$this->_appPath = $appPath;
		$this->_appName = $appName;
		$this->_isAuthentificated = Session::get ( "__auth", false ) && is_object ( Session::get ( "__logonInfo", null ) );
		$cf = $this->_appPath . DS . "config.php";
		//ppd($cf);
		$this->_configs = new Configuration ( $_configs );
		$this->_configs->loadFile ( $this->_appPath . DS . 'config', false );

	}

	public function getClientAssets() {
		return $this->_clientAssets;
	}

	public function addClientAsset($assetName, $group = null) {
		if (is_array ( $assetName ) || is_object($assetName)) {
			if (is_object($assetName) && $assetName instanceof  AssetItem) {
				$this->_clientAssets->add($assetName);
				return $this;
			}
			foreach ( $assetName as $k => $v ) {
				if (is_numeric ( $k )) {
					$this->addClientAsset ( $v );
				} else {
					$this->addClientAsset ( $assetName );
				}
			}
			return $this;
		}
		if ( $this->_clientAssets->contains($assetName)) {
			return $this;
		}
		$this->_clientAssets->add(new AssetItem($assetName,$group));
		return $this;

	}
	function getClientAsset() {
		return $this->_clientAssets;
	}
	public function Assign($var, $val = null) {
		return $this->_vars [$var] = $val;
	}

	function getVars($id = null) {
		return $id ? (isset ( $this->_vars [$id] ) ? $this->_vars [$id] : null) : $this->_vars;
	}

	protected function checkInstall() {
	}

	protected function unserialize($o) {
		return unserialize ( base64_decode ( $o ) );
	}

	protected function serialize($o) {
		return base64_encode ( serialize ( $o ) );
	}

	/**
	 * get Template Instance
	 *
	 * @param boolean $new
	 * @return WebTemplate
	 */
	private function getTemplate($new = false) {

		if ($this->_template == null || $new) {
			$class = $this->getConfig ( "template.class", CGAF_CLASS_PREFIX . "WebTemplate" );
			$class = new $class ( $this );
			$class = $this->initTemplate ( $class );
		}

		if ($new) {
			$class->setAppOwner ( $this );
			return $class;
		}
		if ($this->_template == null) {
			$this->_template = &$class;
		}
		return $this->_template;
	}

	/**
	 *
	 * Enter description here ...
	 * @param Template $value
	 */
	protected function setTemplate($value) {
		$this->_template = $value;
	}

	protected function hasTemplateInstance() {
		return $this->_template !== null;
	}

	/**
	 *
	 * Enter description here ...
	 * @param Template $template
	 */
	protected function initTemplate(&$template) {
		if ($this->parent) {
			$this->parent->initTemplate ( $template );
			$template->assign ( 'title', $this->parent->getConfig ( 'app.title' ) );
		}
		if (! $this->_template) {
			$this->_template = $template;

		}

		return $template;
	}

	/**
	 * @return TLocale
	 */
	function getLocale() {
		if ($this->_locale == null) {
			$this->_locale = new LC ( $this );
		}
		return $this->_locale;
	}

	function getCurrentLocaleId() {
		return $this->getLocale ()->getLocale ();
	}

	function Install() {
		return true;
	}

	function LogOut() {
		if ($this->isAuthentificated ()) {
			Session::destroy ();
			$this->dispatchEvent ( new LoginEvent ( $this, LoginEvent::LOGOUT ) );
		}
		Response::forceContentExpires ();
		return true;
	}

	/**
	 *
	 * @return IAuthentificator
	 */
	function getAuthentificator() {
		if ($this->_auth === null) {
			$class = $this->getConfig ( "authentificator.class", "DBAuthentificator" );

			$this->_auth = new $class ( $this );
		}
		return $this->_auth;
	}

	function setAppInfo($appInfo) {
		if ($this->_appInfo == null) {
			$info = new stdClass ();
			$info = Utils::bindToObject ( $info, $appInfo, true );
			$this->_appInfo = $info;
		}
	}

	/**
	 * @return IApplicationInfo
	 */
	function getAppInfo() {
		if (! $this->_appInfo) {
			$this->_appInfo = AppManager::getAppInfo ( $this );
		}
		return $this->_appInfo;
	}

	function getAppId() {
		$info = null;
		if ($this->_appInfo) {
			$info = $this->getAppInfo ();
		}

		return $info ? $info->app_id : $this->getConfig ( 'app.id' );
	}

	function resetToken() {
		$token = md5 ( uniqid ( rand (), true ) );
		Session::set ( "__token", $token );
		return $token;
	}

	function getToken() {
		$retval = Session::get ( "__token" );
		if (! $retval) {
			$retval = $this->resetToken ();
		}
		return $retval;
	}

	function isValidToken($req = "__token") {
		$st = Session::get ( '__token' );
		$rt = Request::get ( '__token' );
		return $rt !== null && $st === $rt;
	}

	function Authenticate() {
		Response::forceContentExpires ();
		if ($this->isAuthentificated ()) {
			return true;
		}
		if (! $this->isValidToken ()) {
			$this->resetToken ();
			//$this->Log("Login", "Invalid Token " . $args ["username"], false);
			throw new SystemException ( 'error.invalidtoken' );
		}
		if ($this->getConfig ( 'usecaptcha', false )) {
			if (! $this->isValidCaptcha ( "__captcha", true )) {
				throw new SystemException ( 'error.invalidcaptcha' );
			}
		}
		$info = $this->getAuthentificator ()->Authenticate ();
		if ($info) {
			Session::set ( "__logonInfo", $info );
			Session::set ( "__auth", $info->user_id !== ACLHelper::PUBLIC_USER_ID );
			$this->resetToken ();
		} else {
			//setMessageTitle ( "Authentification Error" );
			$this->addMessage ( $this->getAuthentificator ()->getLastError () );
			Session::remove ( "__auth" );
			Session::remove ( "__logonInfo" );
		}
		if ($this->isAuthentificated ()) {
			$this->dispatchEvent ( new LoginEvent ( $this, LoginEvent::LOGIN ) );
		}
		return $this->isAuthentificated ();
	}

	public function handleError($ex) {
		Logger::Error ( "[%s] %s", get_class ( $ex ), $ex->getMessage () );
	}

	public function isAllow($id, $group, $access = 'view') {

		return $this->getACL ()->isAllow ( $id, $group, $access );
	}

	public function getACL() {
		if ($this->_acl === null) {
			$class = $this->getConfig ( "acl.handler", "db" );
			$this->_acl = ACLHelper::getACLInstance($class, $this);

		}
		return $this->_acl;
	}

	protected function setDBConnection($connection) {
		$this->_dbConnection = $connection;
	}

	function getDBConnection() {
		if ($this->_dbConnection == null) {
			$this->_dbConnection = DB::Connect ( $this->getConfigs ( "db" ) );
		}
		return $this->_dbConnection;
	}

	protected function switchApp($appId) {
		if (CGAF::isAllow ( $appId, ACLHelper::APP_GROUP ) && AppManager::isAppIdInstalled ( $appId )) {
			Session::set ( "_appId", $appId );
			return true;
		}
		return false;
	}

	function getConfig($configName, $default = null) {
		$cg = null;
		if ($this->_configs) {
			$cg = $this->_configs->getConfig ( $configName, null );
		}
		return $cg === null ? CGAF::getConfig ( $configName, $default ) : $cg;
	}

	function setConfig($configname, $value) {
		return $this->_configs->setConfig ( $configname, $value );
	}

	/**
	 *
	 * @param $config String
	 */
	function getConfigs($config,$defaults=null) {
		if ($config === null) {
			return $this->_configs;
		}
		$c1 = $this->_configs->getConfigs ( $config );
		return $c1===null ? $defaults : $cl;
	}

	function getAppName() {
		return $this->_appName;
	}

	function getAuthInfo() {
		return $this->getAuthentificator ()->getLogonInfo ();
	}

	function isInitialized() {
		return $this->_initialized;
	}

	function initSession() {

	}

	/**
	 *
	 * @param $event Event
	 */
	function onSessionEvent($event) {

	}
	protected function getDevPath($path=NULL) {
		$ap =  $this->getConfig ( 'livedatapath', 'assets' );
		return Utils::ToDirectory($this->getAppPath()."DevFiles/$ap/$path/");
	}
	function Initialize() {
		if ($this->isInitialized ()) {
			return true;
		}

		if (CGAF::isDebugMode() || Session::get("installmode")) {
			$this->checkInstall();
		}

		if (CGAF_DEBUG) {
			CGAF::addAlowedLiveAssetPath($this->getDevPath());
		}
		$this->_initialized = true;
		$this->initSession ();

		return $this->_initialized;
	}

	function Shutdown() {
		if ($this->_userConfig) {
			$istore = $this->getInternalStoragePath () . DS . 'userconfig' . DS . $this->getACL ()->getUserId () . '.config';
			Utils::makeDir ( dirname ( $istore ) );
			file_put_contents ( $istore, $this->serialize ( $this->_userConfig ) );
		}
	}

	function getAppPath($full = true) {
		if ($full) {
			return $this->_appPath;
		} else {
			return String::FromLastPos ( $this->_appPath, DS );
		}
	}

	function getContent($position = null) {
		return $position;
	}

	function getSharedPath() {
		return CGAF_PATH . DS . "Data" . DS . "Shared" . DS;
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $search
	 * @param unknown_type $search2
	 * @param unknown_type $baseP
	 * @return Ambigous <unknown, mixed>
	 */
	protected function _mergeAssetPath($search, $search2, $baseP = null) {
		$appPath = $this->getAppPath ( true );
		$basePx = $this->getConfig ( "livedatapath", "assets" );

		$baseP = $baseP ? $baseP : $basePx;
		$retval = $search;

		if (is_array ( $search2 )) {
			foreach ( $search2 as $v ) {
				$retval = $this->_mergeAssetPath ( $retval, $v, $baseP );
			}
			return $retval;
		}

		$r = array ();

		$r [] = $baseP . DS . $search2;
		foreach ( $r as $v ) {
			$f = Utils::ToDirectory ( $appPath . DS . $v . DS );
			if (! in_array ( $f, $retval )) {
				$retval [] = $f;
			}
			$f = Utils::ToDirectory ( SITE_PATH . DS . $v . DS );
			if (! in_array ( $f, $retval )) {
				$retval [] = $f;
			}

		}
		return $retval;
	}

	function addAssetPath($prefix, $path) {
		if (! isset ( $this->_assetPath [$prefix] )) {
			$this->_assetPath [$prefix] = array ();
		}
		if (is_array ( $path )) {
			foreach ( $path as $p ) {
				$this->_assetPath [$prefix] [] = $p;
			}
		} else {
			$this->_assetPath [$prefix] [] = $path;
		}
	}

	protected abstract function getAssetPath($data, $prefix = null);

	function getAsset($data, $prefix = null) {
		if (is_array($data)) {
			$retval = array();
			foreach ($data as $k=>$v) {
				if ($v) {
					$asset = $this->getAsset($v);
					if ($asset) {
						$retval[$k] =$asset;
					}
				}
			}
			return $retval;
		}
		if (! is_string ( $data ) || ! $data) {
			return null;
		}
		if (Utils::isLive ( $data )) {
			return $data;
		}

		if (is_file ( $data )) {
			return $data;
		}

		$search = $this->getAssetPath ( $data, $prefix );
		$s = array ();
		$ext = Utils::getFileExt($data,false);
		foreach ( $search as $file ) {
			//search for current application path
			$fname = Utils::ToDirectory ( $file . DS . $data );
			$s [] = $fname;
			if (is_file ( $fname ) || is_dir ( $fname )) {
				return $fname;
			}
			switch ($ext) {
				case "css":
				case "js":
					$alt = Utils::changeFileExt($fname, "min.".$ext);
					$s[] = $alt;
					if (is_file ( $alt ) || is_dir ( $alt )) {
						return $alt;
					}
					break;

				default:
					;
					break;
			}
		}
		return null;
	}

	function getTemporaryPath() {
		return $this->getInternalStorage(".temp",true);
	}

	function getAssetURL() {
		if (! $this->_assetURL) {
			$this->_assetURL = Utils::PathToLive ( $this->getLivePath () );
		}
		return $this->_assetURL;
	}

	function getCachePath() {
		return $this->getTemporaryPath () . 'cache' . DS;
	}

	/**
	 * @return TCacheManager
	 */
	function getCacheManager() {
		if ($this->_cacheManager == null) {

			$class = $this->getConfig ( "cache.engine", "GCacheManager" );
			$this->_cacheManager = new $class ( $this );
			$this->_cacheManager->setCachePath ( $this->getTemporaryPath () );

		}
		return $this->_cacheManager;
	}

	public function getInternalStoragePath() {
		return $this->getAppPath () . DS . $this->getConfig ( "app.internalstorage", "protected" );
	}

	public function getInternalCache() {
		if ($this->_internalCache == null) {
			$class = $this->getConfig ( "cache.engine", "TCacheManager" );
			$this->_internalCache = new $class ( $this );
			$this->_internalCache->setCachePath ( $this->getConfig ( "app.internalcachepath", $this->getInternalStoragePath () . '.cache/' ) );
		}
		return $this->_internalCache;
	}

	function getResource($o, $prefix, $live = true) {
		if ($live) {
			return $this->getLiveData ( $o, $prefix );
		} else {
			return $this->getAsset ( $o, $prefix );
		}
	}

	function getLiveAsset($data, $prefix = null, $callback = null) {
		$asset = $this->getAsset ( $data, $prefix );
		if ($asset !== null) {
			$retval= $this->assetToLive ( $asset );
			if (!$retval) {
				ppd($asset);
				return null;
			}
			$asset =$retval;
		}

		return $asset;
	}

	/**
	 *
	 * @param $data
	 * @param $prefix
	 * @param $callback
	 * @deprecated use getLiveAsset
	 */
	function getLiveData($data, $prefix = null, $callback = null) {
		return $this->getLiveAsset ( $data, $prefix, $callback );
	}

	function getLivePath($sessionBased = false) {
		return Utils::ToDirectory ( SITE_PATH . "assets/applications/" . $this->getAppId () . "/" . ($sessionBased ? session_id () . "/" : "") );
	}

	protected function isAllowToLive($file) {
		if (Utils::isLive ( $file )) {
			return true;
		}

		$file = realpath ( $file );

		if (! String::BeginWith ( $file, $this->getAppPath () )) {
			return CGAF::isAllowAssetToLive ( $file );
		}
		$ext = Utils::getFileExt ( $file, FALSE );
		return in_array ( $ext, array (
				'js',
				'gif',
				'png',
				'jpg',
				'css' ) );
	}
	protected function getLiveAssetPath($asset,$sessionBased=false) {
		$tmp =null;
		if ($asset) {
			$tmp =  str_ireplace($this->getAppPath(true), '', $asset);
		}
		return $this->getLivePath ( $sessionBased ) .  $tmp;
	}
	function assetToLive($asset, $sessionBased = false) {
		if (is_array ( $asset )) {
			$retval = array ();
			foreach ( $asset as $ff ) {
				if (!$ff) continue;
				$file = $this->assetToLive ( $ff, $sessionBased );
				if ($file) {
					$retval [] = $file;
				} elseif (CGAF_DEBUG) {
					Logger::Warning( $ff );
				}
			}
			return $retval;
		}

		if (! $this->isAllowToLive ( $asset )) {

			return null;
		}

		if (strpos ( $asset, '://' ) !== false) {
			return $asset;
		}

		if (! is_file ( $asset )) {
			return null;
		}
		if ( String::BeginWith( $asset,$this->getDevPath())) {
			if (CGAF_DEBUG) {
				$tmp =  str_replace($this->getDevPath(), '', $asset);
				$stemp = $this->getLiveAssetPath($this->getConfig ( 'livedatapath', 'assets' )."/$tmp");
				if (Utils::copyFile($asset, $stemp,array("overwrite"=>true))) {
					$asset = $stemp;
				}else {
					return null;
				}
			}else{
				return null;
			}
		}
		return CGAF::assetToLive($asset);
	}

	function Log($cat, $msg, $success) {
		$filename = $this->_appPath . "/tmp/logs/";
		if (! is_dir ( $filename )) {
			Utils::makeDir ( $filename );
		}
		$m = array (
		DateTime::ATOM,
		$_SERVER ['REMOTE_ADDR'] );
		$msg = implode ( ",", $m ) . ",$msg";
		$filename .= strtolower ( $cat ) . '-' . ($success ? "success" : "failed") . ".log";
		file_put_contents ( $filename, $msg, FILE_APPEND | LOCK_EX );
	}

	function isAuthentificated() {
		return Session::get ( "__auth", false ) && is_object ( Session::get ( "__logonInfo", null ) ) && ACLHelper::getUserId () !== ACLHelper::PUBLIC_USER_ID;
	}

	function Run() {
		$this->initRun ();

		//Session::Close();
	}

	protected function initRun() {
		return true;
	}

	/**
	 * @return Configuration
	 */
	protected function _UserConfigInstance($uid = null) {
		$cid = ACLHelper::isAllowUID ( null );
		$uid = $uid == null ? $cid : $uid;
		if ($cid !== $uid) {
			$istore = $this->getInternalStoragePath () . DS . 'userconfig' . DS . $uid . '.config';
			$cfg = array ();
			if (is_file ( $istore )) {
				$cfg = $this->unserialize ( file_get_contents ( $istore ) );
			} else {
				$cfg = new Configuration ();
			}
			return $cfg;
		} elseif ($this->_userConfig === null) {
			$istore = $this->getInternalStoragePath () . DS . 'userconfig' . DS . $cid . '.config';
			Utils::makeDir ( dirname ( $istore ) );
			$cfg = array ();
			if (is_file ( $istore )) {
				$cfg = $this->unserialize ( file_get_contents ( $istore ) );
			}
			$this->_userConfig = new Configuration ( $cfg, false );
		}
		return $this->_userConfig;
	}

	function setUserConfig($configName, $value, $uid = null) {
		$uid = ACLHelper::isAllowUID ( $uid );
		$instance = $this->_UserConfigInstance ( $uid );
		return $instance ? $instance->setConfig ( $configName, $value ) : null;
	}

	function getUserConfig($configName, $def = null, $uid = null) {
		$instance = $this->_UserConfigInstance ( $uid );
		$r = $instance->getConfig ( $configName, $this->getConfig ( 'userprivacy.' . $configName, null ) );
		return $r !== null ? $r : $def;
	}

	public function getInternalStorage($path, $create = false) {
		$iPath = Utils::ToDirectory ( $this->getConfig ( "app.internalstorage", CGAF_PATH . '/protected/' ) . "/" . $path );
		if (is_readable ( $iPath )) {
			return $iPath;
		} else {
			if ($create) {
				return Utils::makeDir ( $iPath );
			}
			Logger::Warning ( "Unable to find Internal Data $iPath" );
		}
		return null;
	}

	public function loadClass($classname) {
		$p = Utils::ToDirectory ( $this->getAppPath () . '/classes/' );
		$s = array (
		$classname . CGAF_CLASS_EXT,
		strtolower ( $classname ) . CGAF_CLASS_EXT );
		foreach ( $s as $ss ) {
			if (is_file ( $p . $ss )) {
				return CGAF::Using ( $p . $ss );
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

	function getClassPath() {
		return $this->getAppPath ( true ) . DS . $this->getConfig ( "app.classpath", 'classes' );
	}

	function unhandledNameSpace($namespace) {

		//throw new SystemException($namespace.' NOT FOUND');
		return false;
	}
}
?>
