<?php
//Define Cosntant

//defined('CGAF_BEGIN_TIME') or define('CGAF_BEGIN_TIME', microtime(true));
use System\ACL\ACLHelper;
use System\Applications\IApplication;
use System\Configurations\Configuration;
use System\DB\DB;
use System\DB\DBQuery;
use System\Exceptions\AccessDeniedException;
use System\Exceptions\AssetException;
use System\Exceptions\SystemException;
use System\Locale\Locale;
use System\Session\Session;
use System\Session\SessionEvent;

defined("CGAF") or define("CGAF", true);
defined('CGAF_CLASS_PREFIX') or define('CGAF_CLASS_PREFIX', '');
defined('DS') or define("DS", DIRECTORY_SEPARATOR);
defined('NSS') or define('NSS', '\\'); //namespace separator
defined('CGAF_VERSION') or define('CGAF_VERSION', '2.0.0');

final class CGAF
{
    const APP_ID = '__cgaf';
    private static $_internalClassCache = array();
    private static $_dbConfigs;
    private static $_initialized = false;
    private static $_namespaces = array();
    private static $_messages;
    private static $_classPath = array();
    private static $_lastError;
    private static $_msgTitle;
    private static $_autoLoadCallBack = array();
    private static $_acl;
    private static $_devmode = false;
    /**
     * @var \System\DB\IDBConnection
     */
    private static $_dbConnection;
    private static $_locale;
    private static $_cacheManager;
    /**
     * @var \System\Cache\Engine\ICacheEngine
     */
    private static $_internalCache;
    private static $_benchmark;
    private static $_nsClass = array();
    private static $_isDebugMode = null;
    //private static $_installMode = false;
    private static $_shutdown = false;
    private static $_searchPath = array();
    private static $_allowedLivePath = array();
    private static $loadedNamespaces = array();
    private static $_running = false;
    /**
     * @var System\Configurations\IConfiguration
     */
    private static $_configuration;
    private static $_internalStorage = null;

    public static function shutdown_handler()
    {
        if (!self::$_running) {
            return;
        }
        self::$_running = false;
        if (self::$_initialized) {
            file_put_contents(self::getInternalStorage('.cache', false).'classes' ,serialize(self::$_internalClassCache));
        }

        if (class_exists('AppManager', false)) {
            AppManager::Shutdown();
        }
        if (class_exists('Logger', false)) {
            Logger::Flush();
        }
        if (class_exists('Response', false)) {
            Response::Flush();
        }
        while (@ob_end_flush());
        self::$_initialized = false;
    }

    public static function isRunning()
    {
        return self::$_running;
    }

    public static function startTime()
    {
        return self::$_benchmark;
    }

    public static function isFunctionExist($f, $throw = false)
    {
        if (!function_exists($f)) {
            if ($throw) {
                throw new SystemException('function: ' . $f . ' Not Exist');
            }
            return false;
        }
        return true;
    }

    private static function getCacheRequestFile($sessionBase = false)
    {
        $path = self::getInternalStorage(
            '.cache/request/' . ($sessionBase ? session_id() . '/' : ''),
            false, true);
        $f = $path . DS . md5(isset($_SERVER["REQUEST_URI"]) ? $_SERVER['REQUEST_URI'] : $_SERVER['PWD'] . DS . $_SERVER['PHP_SELF']);
        return $f;
    }

    private static function exitIfNotModified()
    {
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
            $last_modified = $fc['timeCreated'] >= $fc['lastModified'] ? $fc['timeCreated']
                : $fc['lastModified'];
            $validUntil = $last_modified + (60 * 60 * 24 * $fc['validDay']);
            $valid = $fc['validDay'];
            if ($last_modified > $validUntil) {
                pp('removed');
                \Utils::removeFile($f);
                return;
            }
            //ppd(date('d M Y', $validUntil));
            if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
                $if_modified_since = strtotime(
                    preg_replace('/;.*$/', '',
                        $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
                //ppd($if_modified_since.'-'.$validUntil);
                if ($if_modified_since <= $validUntil) {
                    header_remove('Cache-Control');
                    header(
                        'Cache-Control: public, max-age='
                        . ($valid * 30 * 24 * 60 * 60));
                    header(
                        'Last-Modified:'
                        . gmdate("D, d M Y H:i:s", $last_modified)
                        . ' GMT');
                    //ppd(gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')));
                    header(
                        'Expires:'
                        . gmdate("D, d M Y H:i:s",
                            \DateUtils::dateAdd(time(),
                                $valid . ' day')) . ' GMT');
                    header("HTTP/1.0 304 Not Modified");
                    CGAF::doExit();
                    exit();
                }
            }
        }
    }

    public static function cacheRequest($lastModified, $valid,
                                        $sessionBase = false, $originalFile = null)
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $f = self::getCacheRequestFile($sessionBase);
            @file_put_contents($f,
                serialize(
                    array(
                        'originalURL' => $_SERVER['REQUEST_URI'],
                        'timeCreated' => time(),
                        'lastModified' => $lastModified,
                        'validDay' => $valid,
                        'originalFile' => $originalFile
                    )));
            header('Cache-Control: public, max-age='
                . ($valid * 30 * 24 * 60 * 60));
            header(
                'Last-Modified:' . gmdate("D, d M Y H:i:s", $lastModified)
                . ' GMT');
            //ppd(gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')));
            header(
                'Expires:'
                . gmdate("D, d M Y H:i:s",
                    \DateUtils::dateAdd(time(), $valid . ' day'))
                . ' GMT');
        }
    }

    public static function removeCacheRequest($uri = null)
    {
        $uri = $uri ? $uri
            : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']
                : null);
        if ($uri) {
            \Utils::removeFile(self::getCacheRequestFile(true));
            \Utils::removeFile(self::getCacheRequestFile(false));
        }
    }

    public static function doExit()
    {
        if (class_exists('Response', false)) {
            if (!System::isConsole()) {
                Response::clearBuffer();
            }
        }
        self::shutdown_handler();
        exit(0);
    }

    public static function setMessageTitle($msg)
    {
        self::$_msgTitle = $msg;
    }

    public static function addMessage($msg)
    {
        if ($msg === (array)$msg) {
            foreach ($msg as $m) {
                self::$_messages[] = $m;
            }
        } else {
            self::$_messages[] = $msg;
        }
    }

    public static function getMessageTitle()
    {
        return self::$_msgTitle ? self::$_msgTitle : "Server Messages";
    }

    public static function getMessages()
    {
        return self::$_messages;
    }

    public static function getLastError()
    {
        return self::$_lastError;
    }

    public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        self::$_lastError = "$errno@$errfile [$errline] : $errstr";
        if (self::isDebugMode()) {
            ppd(self::$_lastError);
        }
        if (class_exists('Logger')) {
            Logger::write(self::$_lastError, $errno);
        } elseif (self::isDebugMode()) {
            pp(self::$_lastError);
        }
    }

    public static function exception_handler(\Exception $ex)
    {
        if (self::$_shutdown || (\System::isConsole() && CGAF_DEBUG)) {
            die($ex->getMessage());
            return false;
        }
        if (\Request::isDataRequest()) {
            header('HTTP/1.0 400 Bad Request');
            echo $ex->getMessage();
            exit(0);
        }
        if (class_exists("AppManager", false)) {
            if (AppManager::isAppStarted()) {
                $r = AppManager::getInstance()->handleError($ex);
                echo $r;
                return $r;
            }
        }
        if ($ex instanceof AccessDeniedException) {
            \Response::Redirect(
                URLHelper::add(BASE_URL, 'auth',
                    array(
                        'redirect' => \Request::getOrigin()
                    )));
        }
        Logger::Error("[%s] %s", get_class($ex), $ex->getMessage());
        self::doExit();
        return true;
    }

    static function getConfig($name, $def = null)
    {
        if ($name === 'installed') {
            return self::isInstalled();
        }
        switch (strtolower($name)) {
            case 'disableacl':
                $retval = self::getConfiguration()->getConfig($name, $def);
                return self::isDebugMode() ? $retval : false;
                break;
            default:
                ;
                break;
        }
        return self::getConfiguration()->getConfig($name, $def);
    }

    /**
     * @static
     * @return bool
     */

    static function isInstalled()
    {
        return self::getConfiguration()->getConfig('cgaf.installed', false);
    }

    public static function reloadConfig()
    {
        if (!self::isInstalled()) {
            self::$_dbConnection = null;
        }
    }

    /**
     * return IConfiguration
     */

    static function &getConfiguration()
    {
        if (self::$_configuration == null) {
            self::$_configuration = new Configuration(null, false);
            self::$_configuration->loadFile(CGAF_PATH . 'config.php');
        }
        return self::$_configuration;
    }

    static function getConfigs($group, $def = null)
    {
        return self::getConfiguration()->getConfigs($group, $def);
    }

    public static function isInitialized()
    {
        return self::$_initialized;
    }

    public static function isDebugMode()
    {
        if (defined('CGAF_DEBUG')) {
            self::$_isDebugMode = CGAF_DEBUG;
        } elseif (self::$_isDebugMode == null) {
            $debugMode = self::getConfig('cgaf.debugmode', false);
            if ($debugMode) {
                if (!defined('CGAF_DEBUG')) {
                    if (isset($_SERVER['REMOTE_ADDR'])) {
                        self::$_isDebugMode = self::isRemoteDebugAllow();
                    } else {
                        self::$_isDebugMode = false;
                    }
                }
            }
            define('CGAF_DEBUG', self::$_isDebugMode);
        }
        return self::$_isDebugMode;
    }

    public static function isRemoteDebugAllow()
    {
        static $debug;
        if ($debug == null) {
            $hosts = explode(',',
                self::getConfig('cgaf.allowedebughost',
                    $_SERVER['SERVER_ADDR']));
            $debug = in_array($_SERVER['REMOTE_ADDR'], $hosts);
        }
        return $debug;
    }

    /**
     * Initialize framework
     *
     * @param bool $initonly
     * @throws Exception
     * @return boolean
     */

    static function Initialize($initonly = false)
    {
        if (self::$_initialized) {
            return true;
        }
        //set_time_limit(0);
        if (!defined("CGAF_PATH")) {
            define("CGAF_PATH", realpath(dirname(__FILE__) . "/..") . DS);
        }
        ini_set('max_execution_time', 30);
        if (!defined("SITE_PATH")) {
            define("SITE_PATH", realpath(dirname(__FILE__) . "/../") . DS);
        }
        if (!defined("CGAF_CLASS_EXT")) {
            define("CGAF_CLASS_EXT", ".php");
        }
        define('CGAF_SYS_PATH', CGAF_PATH . 'System' . DS);
        self::addClassPath('system', CGAF_SYS_PATH);
        self::$_searchPath['System'] = array(
            CGAF_SYS_PATH
        );
        self::Using(array(
            CGAF_SYS_PATH . 'baseobject.php',
            CGAF_SYS_PATH . 'system.php',
            CGAF_SYS_PATH . 'utils.php',
            CGAF_SYS_PATH . 'DB/IDBAware.php',
            CGAF_SYS_PATH . 'Interfaces/IRenderable.php',
            CGAF_SYS_PATH . 'Applications/IApplication.php',
            CGAF_SYS_PATH . 'Configurations/IConfigurable.php',
            CGAF_SYS_PATH . 'Configurations/IConfiguration.php',
            CGAF_SYS_PATH . 'Configurations/Configuration.php',
            CGAF_SYS_PATH . 'Configurations/Parsers/IConfigurationParser.php',
            CGAF_SYS_PATH . 'Configurations/Parsers/PHPParser.php',
            CGAF_SYS_PATH . 'Strings.php',
            CGAF_SYS_PATH . 'Convert.php',
            CGAF_SYS_PATH . 'MVC/MVCHelper.php'
        ));
        $cachefile =realpath(self::getInternalStorage('.cache', false).'classes');
        if ($cachefile) {
            self::$_internalClassCache  = unserialize(file_get_contents($cachefile));
        }
        System::Initialize();
        if (!defined("CGAF_CONTEXT")) {
            if (php_sapi_name() == 'cli' || php_sapi_name() == 'cgi-fcgi') {
                $def = "Console";
            } else {
                $def = "Web";
            }
            define("CGAF_CONTEXT", self::getConfig("Context", $def));
        }
        if (!defined('BASE_URL')) {
            $s = self::getConfig('cgaf.baseurl');
            if (!$s) {
                if (CGAF_CONTEXT == "Web" && isset($_SERVER['HTTP_HOST'])) {
                    /*** check for https ***/
                    $protocol = isset($_SERVER['HTTPS'])
                    && ($_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
                    /*** return the full address ***/
                    $s = substr($_SERVER['PHP_SELF'], 0,
                        strripos($_SERVER['PHP_SELF'], "/"));
                    $port = ($_SERVER["SERVER_PORT"] !== "80" ? ":"
                        . $_SERVER["SERVER_PORT"] : "");
                    if ($protocol === 'https'
                        && $_SERVER["SERVER_PORT"] === '443'
                    ) {
                        $port = null;
                    }
                    $s = $protocol . '://' . $_SERVER['HTTP_HOST'] . $port . $s
                        . '/';
                }
            }
            define('BASE_URL', $s);
        }
        $tz = self::getConfig('date.timezone', ini_get('date.timezone'));
        $tz = $tz ? $tz : 'UTC';
        date_default_timezone_set($tz);
        ini_set('date.timezone', $tz);
        if (!defined('ASSET_URL')) {
            define("ASSET_URL",
            self::getConfig("cgaf.asseturl",
                BASE_URL
                . self::getConfig('livedatapath', 'assets')
                . '/'));
        }
        if (!defined('ASSET_PATH')) {
            define("ASSET_PATH",
            self::getConfig("cgaf.assetpath", SITE_PATH . 'assets' . DS));
        }
        ini_set("session.save_path",
            self::getInternalStorage('sessions', false));
        register_shutdown_function("CGAF::shutdown_handler");
        $paths = self::getConfiguration()
            ->getConfigs('cgaf.paths.app', array());
        $rpath = array();
        foreach ($paths as $v) {
            if ($p = realpath($v)) {
                $rpath[] = $p . DS;
            } else if (self::isDebugMode()) {
                Logger::Error('Application Path not found @' . $v);
            }
        }

        if (!defined("CGAF_APP_PATH")) {
            define("CGAF_APP_PATH",
            self::getConfig('cgaf.paths.defaultAppPath',
                isset($rpath[0]) ? $rpath[0]
                    : CGAF_PATH . "Applications"));
        }
        if (!is_dir(CGAF_APP_PATH)) {
            die('Applications path not found');
        }
        if (!in_array(CGAF_APP_PATH, $rpath)) {
            $rpath[] = CGAF_APP_PATH;
        }
        self::getConfiguration()->setConfig('cgaf.paths.app', $rpath);
        if (CGAF_APP_PATH == null || realpath(CGAF_APP_PATH) == null) {
            die("Application Path Not Found" . CGAF_APP_PATH);
        }
        $shared = self::getConfigs('cgaf.paths.shared', CGAF_PATH . 'shared/');
        if (!$shared === (array)$shared) {
            $shared = array(
                $shared
            );
            self::$_configuration->setConfig('cgaf.paths.shared', $shared);
        }
        foreach ($shared as $s) {
            self::addClassPath('system', $s);
            self::addAlowedLiveAssetPath(
                $s . self::getConfig("assetpath", 'assets'));
            self::addClassPath('system', $s . 'classes' . DS, false);
        }
        \System\MVC\MVCHelper::Initialize();
        if (isset($_SERVER['REMOTE_ADDR']) && !$initonly) {
            if ($token = \Request::get('__devtoken', '', true, 'g')) {
                //if ( self::isRemoteDebugAllow()) {
                if (!isset($_COOKIE['__devtoken'])) {
                    setcookie('__devtoken',
                        md5(
                            self::getConfig('cgaf.devtoken')
                            . $_SERVER['REMOTE_ADDR']), 0, '/');
                    \Response::Redirect(BASE_URL);
                }
                //}
            }
            $dt = isset($_COOKIE['__devtoken']) ? $_COOKIE['__devtoken'] : null;
            if ($dt
                === md5(
                    self::getConfig('cgaf.devtoken')
                    . $_SERVER['REMOTE_ADDR'])
            ) {
                self::$_devmode = true;
            } else {
                setcookie('__devtoken', null);
            }
        }
        if (!self::isDebugMode()) {
            error_reporting(E_ERROR | E_WARNING | E_PARSE ^ E_STRICT);
            set_error_handler("CGAF::error_handler");
            set_exception_handler("CGAF::exception_handler");
            if (function_exists('xdebug_disable')) {
                xdebug_disable();
            }
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            //ppd(ini_get('display_errors'));
        }
        set_exception_handler("CGAF::exception_handler");
        //ppd( self::getInternalStorage('log',false) . DS . 'cgaf.error.log' );
        $errors = self::getConfigs(
            'cgaf.errors' . (self::isDebugMode() ? '.debug' : ''), array());
        foreach ($errors as $k => $v) {
            if ($k === 'debug') {
                continue;
            }
            switch ($k) {
                case 'log_errors':
                    if ($v === null) {
                        $errors[$k] = true;
                    }
                    break;
                case 'error_log':
                    if ($v === null) {
                        $errors[$k] = self::getInternalStorage('log', false)
                            . DS;
                    }
                    break;
            }
        }
        if (self::isDebugMode()) {
            self::$_configuration->setconfig('errors', $errors);
        }
        define('CGAF_LIB_PATH',
            self::getConfig('cgaf.paths.libs', CGAF_PATH . DS . 'Libs')
            . DS);
        self::addClassPath('Libs', CGAF_LIB_PATH, false);
        self::addClassPath('system', CGAF_LIB_PATH, false);
        if (!defined("CGAF_VENDOR_PATH")) {
            define("CGAF_VENDOR_PATH",
            self::getConfig('cgaf.paths.vendor',
                CGAF_PATH . 'vendor' . DS), false);
        }
        self::addClassPath('Vendor', CGAF_VENDOR_PATH);
        if (!defined("CGAF_LIVE_PATH")) {
            define("CGAF_LIVE_PATH", CGAF_PATH);
        }
        self::addAlowedLiveAssetPath(
            CGAF_PATH . self::getConfig("livedatapath", "assets"));
        self::using('System.' . CGAF_CONTEXT . '.Request');
        self::using('System.' . CGAF_CONTEXT . '.Response');
        if (!$initonly) {
            Session::getInstance()
                ->addEventListener('*',
                    array(
                        'CGAF',
                        'onSessionEvent'
                    ));
            try {
                AppManager::initialize();
            } catch (Exception $e) {
                throw $e;
            }
        }
        self::$_initialized = true;
        return true;
    }

    static function getTempPath()
    {
        return self::getConfig("temp.path", CGAF_PATH . 'tmp' . DS);
    }

    private static function offlineRedirect($code)
    {
        if (!System::isConsole()) {
            header("Location: " . BASE_URL . 'offline.php?code=1');
            exit(0);
            //Response::redirect(BASE_URL . 'offline.php?code=1');
        } else {
            Response::writeln(__('offline.' . $code, 'Offline : ' . $code));
            CGAF::doExit();
        }
    }

    /**
     * @static
     *
     * @param \System\Events\Event $event
     * @param null $sid
     *
     * @throws System\Exceptions\SystemException
     */

    public static function onSessionEvent($event, $sid = null)
    {
        if (!($event instanceof SessionEvent)) {
            throw new SystemException('invalid event');
        }
        /**
         * @var \ISession $sender
         */
        $sender = $event->sender;
        $q = new DBQuery(self::getDBConnection());
        $sid = $sid ? $sid : $sender->getId();
        self::Using('Models.session');
        $sess = new \System\Models\SessionModel();
        switch ($event->type) {
            case SessionEvent::SESSION_GC:
            case SessionEvent::SESSION_STARTED:
                $uid = self::getACL()->getUserId();
                if ($event->type == SessionEvent::SESSION_STARTED) {
                    $q->clear();
                    $q
                        ->addSQL(
                            'SELECT * from #__session  where session_id='
                            . $q->quote($sid));
                    $o = $sess->load($sid);
                    if (!$o || !isset($o->session_id) || !$o->session_id) {
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
                break;
            case SessionEvent::DESTROY:
                $q->clear()->addTable('session')
                    ->where('session_id=' . $q->quote($sid))->delete();
                try {
                    $q->exec();
                } catch (\Exception $e) {
                    ppd($e);
                }
        }
    }

    private static function handleAssetNotFound()
    {
        $url = \URLHelper::explode($_REQUEST['__url']);

        $f = \Request::getInstance()->secureVar(implode('/', $url['path']));
        $ext = Utils::getFileExt($f, false);
        $alt = null;
        $asset = str_ireplace('assets/', '', $f);
        try {
            $ass = AppManager::getInstance()->getAsset($asset);
            if (!$ass) {
                $ass = substr($asset, strpos($asset, '/', 1) + 1);
                $ass = AppManager::getInstance()
                    ->getAsset(substr($ass, 0, strpos($ass, '?')));
            }
            if ($ass && self::isAllowAssetToLive($ass)) {
                Streamer::Stream($ass);
                return true;
            }
            if ($asset == "icons/favicon.ico") {
                $alt = SITE_PATH . "assets/favicon.ico";
            } else {
                switch (strtolower($ext)) {
                    case "png":
                    case "jpg":
                    case "gif":
                    case "jpeg":
                        $alt = SITE_PATH . "assets/images/alts/empty." . $ext;
                        break;
                }
            }
            if ($alt && is_file($alt)) {
                Streamer::Stream($alt);
                return true;
            }
        } catch (\Exception $e) {
            throw $e;
        }
        throw new AssetException("asset not found %s", $f);
    }

    static function Run($appName = null, $installMode = false)
    {
        //ppd($_FILES);
        self::$_running = true;
        if (!self::Initialize()) {
            die("unable to initialize framework");
        }
        if (!self::isInstalled() && !$installMode) {
            Response::redirect(BASE_URL . 'Applications/Install/');
            //return self::offlineRedirect(0);
            return true;
        }
        if (!self::$_devmode && self::getConfig('offline')) {
            self::offlineRedirect(1);
        }
        if (\Request::isMobile()) {
            //ppd('mobile');
        }
        self::exitIfNotModified();
        if (isset($_REQUEST["__url"]) && $_REQUEST["__url"] == 'favicon.ico') {
            //$icon = 'assets/icons/favicon.ico';
            $_REQUEST["__url"] = 'assets/icons/favicon.ico';
        }
        if (isset($_SERVER["REQUEST_URI"]) && substr($_SERVER["REQUEST_URI"], 0, 7) == '/assets') {
            $_REQUEST["__url"] = substr($_SERVER["REQUEST_URI"], 7);
            return self::handleAssetNotFound();
        }
        $refAppId = null;
        $retval = null;
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
                AppManager::setActiveApp($instance);
                $instance = $appName;
            }
            $refAppId = Request::get('__reffAppId');
            $appId = Request::get('__appId');
            if (!$instance && $appId) {
                try {
                    if (AppManager::isAppInstalled($appId, false)) {
                        $appName = $appId;
                    } else {
                        $instance = AppManager::getInstance(self::APP_ID);
                        AppManager::setActiveApp($instance);
                        throw new AccessDeniedException(
                            'Application ' . $appId . ' not installed');
                    }
                } catch (Exception $e) {
                    throw $e;
                }
            }
            $cgaf = Request::get('__cgaf', null, true);
            switch (strtolower($cgaf)) {
                case '__switchapp':
                    $appId = Request::get('id');
                    if ($appId && AppManager::isAppInstalled($appId, false)) {
                        Session::set('__appId', $appId);
                        Response::Redirect('/');
                        return true;
                    }
                    break;
                case 'reset':
                    //TODO Validate security
                    if (CGAF_DEBUG) {
                        self::getACL()->clearCache();
                        try {
                            AppManager::getInstance()->getACL()->clearCache();
                            AppManager::getInstance(self::APP_ID)->getACL()
                                ->clearCache();
                            //AppManager::getInstance()->Reset();
                            Session::set('__clientInfo', null);
                            Session::remove('__appId');
                            Session::clearPast();
                        } catch (Exception $e) {
                            ppd($e);
                        }
                        \Response::Redirect(APP_URL);
                    }
                    break;
                default:
                    try {
                        $instance = AppManager::getInstance($appName);
                    } catch (Exception $ex) {
                        //if (self::isDebugMode()) {
                        //	throw $ex;
                        //}
                        if ($appName !== self::APP_ID) {
                            $instance = AppManager::getInstance(self::APP_ID);
                        }
                    }
                    break;
            }
        }
        if ($refAppId) {
            try {
                $instance = AppManager::getInstance($refAppId);
                if (!$instance) {
                    throw new SystemException(
                        "Refference Application Instance not found");
                }
            } catch (\Exception $e) {
                throw new SystemException(
                    "Refference Application Instance not found");
            }
        } else {
            if (!$instance) {
                throw new SystemException(
                    "Application Instance not found/Access Denied");
            }
            Session::set('__appId', $instance->getAppId());
        }
        if (isset($_REQUEST["__url"])
            && Strings::BeginWith($_REQUEST["__url"], "assets/")
        ) {
            return self::handleAssetNotFound();
        }
        Response::StartBuffer();
        $retval = $instance->Run();
        if (is_object($retval) && $retval instanceof \Exception) {
            /** @noinspection PhpParamsInspection */
            self::exception_handler($retval);
        }
        if ($retval) {
            Response::write($retval);
        }
        Response::EndBuffer(true);
        return true;
    }

    public static function addClassPath($nsName, $path, $first = true)
    {
        //return NSHelper::addClassPath($nsName, $path, $first);
        if ($path === (array)$path) {
            foreach ($path as $p) {
                self::addClassPath($nsName, $p);
            }
            return true;
        }
        $path = realpath($path);
        if (!$path) {
            return false;
        }
        $path .= DS;
        $nsName = str_replace('.', DS, strtolower($nsName));
        if (!isset(self::$_classPath[$nsName])) {
            self::$_classPath[$nsName] = array();
        }
        $old = self::$_classPath[$nsName];
        if (is_string($path)) {
            $path = array(
                $path
            );
        }
        foreach ($path as $p) {
            if ($p[strlen($p) - 1] != DS)
                $p .= DS;
            if (!in_array($p, $old)) {
                $old[] = $p;
            }
        }
        if ($first) {
            self::$_classPath[$nsName] = array_reverse($old, false);
        } else {
            self::$_classPath[$nsName] = $old;
        }
        return true;
    }

    public static function getClassPath($nsname = null)
    {
        if ($nsname) {
            $nsname = strtolower($nsname);
            return isset(self::$_classPath[$nsname]) ? self::$_classPath[$nsname]
                : null;
        }
        return self::$_classPath;
    }

    /**
     * @return null|IApplication
     */
    public static function getActiveApp()
    {
        if (class_exists('AppManager', false)) {
            if (AppManager::isAppStarted()) {
                return AppManager::getInstance();
            }
        }
        return null;
    }

    public static function getDBConfigs()
    {
        if (!self::$_dbConfigs) {
            $args = self::getConfigs("cgaf.db", array());
            $defargs = self::getConfigs('db');
            foreach ($defargs as $k => $v) {
                if (!isset($args[$k])) {
                    $args[$k] = $v;
                }
            }
            self::$_dbConfigs = $args;
        }
        return self::$_dbConfigs;
    }

    /**
     * @param $f
     * @return mixed
     */
    protected static function toNS($f)
    {
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

    /*private static function nsNormalize($ns, $p)
    {
        if (!class_exists("Utils", false)) {
            return $ns;
        }
        $p = Utils::ToDirectory($p);
        $rns = self::$_classPath[$ns];
        $p = str_replace($rns, "", $p);
        //$nns = substr ( $ns, 0, strpos ( $ns, "." ) );
        return $p;
    }

    private static function nsDebug($cat, $ns, $f)
    {
        self::$_nsDebug[$ns][$cat][] = $f;
    }*/

    private static function UsingDir($fname)
    {
        $dir = opendir($fname);
        $d = readdir($dir);
        while ($d) {
            if (substr($d, 0, 1) !== "." && is_file($fname . DS . $d)) {
                self::Using($fname . DS . $d, false);
            }
            $d = readdir($dir);
        }
        closedir($dir);
        return true;
    }

    private static function checkUsing($fname, $check = true)
    {
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
                CGAF_PATH
            );
            if (class_exists("AppManager", false)) {
                if (AppManager::isAppStarted()) {
                    $path = AppManager::getInstance()->getClassPath();
                    $spath = array_merge($spath,
                        array(
                            $path
                        ));
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
                } elseif (Strings::BeginWith($namespace, $k, true)) {
                    $tfname = Utils::ToDirectory(
                            $path . DS
                            . substr(str_replace('.', DS, $namespace),
                                strlen($k) + 1)) . CGAF_CLASS_EXT;
                    if (self::Using($tfname, false)) {
                        return true;
                    }
                }
            }
            if (class_exists("AppManager", false)) {
                if (AppManager::isAppStarted()) {
                    return AppManager::getInstance()
                        ->unhandledNameSpace($namespace);
                }
            }
        }
        return false;
    }

    private static function _toNS($ns)
    {
        //$o = $ns;
        $ext = substr($ns, strrpos($ns, '.', -4));
        foreach (self::$_classPath as $k => $v) {
            foreach ($v as $p) {
                //if (substr($ns, 0, strlen($p)) === $p) {
                if (strpos($ns,$p) === 0){
                    $ns = $k . DS . substr($ns, strlen($p));
                }
            }
        }
        if ($ext === CGAF_CLASS_EXT) {
            $ns = substr($ns, 0, strlen($ns) - strlen($ext));
        }
        $ns = str_replace(CGAF_PATH, '', $ns);
        $ns = str_replace(
            array(
                '.',
                DS . DS
            ), DS, $ns);
        //$ns = str_replace(DS . DS, DS, $ns);
        return $ns;
    }

    private static function _getFileOfNS($ns)
    {
        $retval = null;
        $first = null;
        $ns = str_replace('.', DS, $ns);
        $ns = self::toDirectory($ns);
        if (strpos($ns, DS) !== false) {
            $first = substr($ns, 0, strpos($ns, DS));
            $fns = substr($ns, strlen($first) + 1);
            $cfname = substr($ns, strrpos($ns, DS) + 1);
            $mpath = substr($ns, strpos($ns, DS) + 1);
            $mpath = substr($mpath, 0, strrpos($mpath, DS));
        } else {
            $fns = $ns;
            $first = 'System';
            $cfname = $ns;
            $mpath = '';
        }
        $spath = self::getClassPath($first);
        $fns = str_replace(NSS, DS, $fns);
        if ($spath) {
            foreach ($spath as $path) {
                $fname = $path . $fns;
                if (file_exists($fname . CGAF_CLASS_EXT)) {
                    $retval = Utils::arrayMerge($retval,
                        $fname . CGAF_CLASS_EXT, true);
                } elseif (file_exists($path . strtolower($fns) . CGAF_CLASS_EXT)) {
                    $retval = Utils::arrayMerge($retval,
                        $path . strtolower($fns) . CGAF_CLASS_EXT, true);
                } elseif (is_dir($fname)) {
                    //$files = array();
                    $retval = \Utils::arrayMerge($retval,
                        \Utils::getDirFiles($fname . DS, $fname . DS,
                            FALSE, "/\\" . CGAF_CLASS_EXT . "/i"),
                        true);
                } elseif ($mpath) {
                    $fname = self::ToDirectory(
                        $path . $mpath . DS . strtolower($cfname)
                        . CGAF_CLASS_EXT);
                    if (is_file($fname)) {
                        $retval = \Utils::arrayMerge($retval,
                            array(
                                $fname
                            ));
                    }
                }
            }
        } else {
            Logger::Warning(
                'unhandled namespace parent of ' . $first . ' at ' . $ns);
        }
        return $retval;
    }

    public static function Using($namespace = null, $throw = true,$checkfile=true)
    {
        if ($namespace === null) {
            return self::$_namespaces;
        }
        if ($namespace === (array)$namespace) {
            $retval = array();
            foreach ($namespace as $k => $v) {
                $retval[$k] = self::Using($v, true,$checkfile);
            }
            return $retval;
        }

        $app = null;

        $star = false;
        if (substr($namespace, strlen($namespace) - 1) === '*') {
            $namespace = substr($namespace, 0, strlen($namespace) - 2);
            $star = true;
        }

        $nsnormal = self::_toNS($namespace);
        $rpath =$checkfile ? realpath($namespace) : $namespace;
        if (!$star && $rpath) {
            if ($checkfile && is_dir($rpath)) return self::UsingDir($rpath);
            $namespace = $rpath;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $namespace = strtolower($namespace);
                $nsnormal = strtolower($nsnormal);
            }
            if (!isset(self::$_namespaces[$nsnormal])) {
                self::$_namespaces[$nsnormal] = array();
            }
            if (!in_array($namespace, self::$_namespaces[$nsnormal])) {
                self::$_namespaces[$nsnormal][] = $namespace;
                /** @noinspection PhpIncludeInspection */
                require $namespace;
            }
            return true;
        }
        $cache = null;
        $app = null;
        if (class_exists('AppManager', false)) {
            if (\AppManager::isAppStarted()) {
                $app = \AppManager::getInstance();
                $cache = $app->getCached('namespaces', $nsnormal);
            }
        }
        if ($cache) {
            foreach ($cache as $v) {
                self::Using($v);
            }
            return true;
        } else {
            $f = self::_getFileOfNS($nsnormal);
            if ($app) {
                $app->putCache('namespaces', $nsnormal, $f);
            }
            if ($f && $f === (array)$f) {
                foreach ($f as $v) {
                    self::Using($v);
                }
                return true;
            } elseif ($f) {
                return self::Using($f);
            }
        }

        if ($throw) {
            throw new System\Exceptions\SystemException(
                "Namespace not found " . $namespace);
        }
        return false;
    }

    public static function getUserStorage($uid = null, $check = true)
    {
        $uid = $check ? ACLHelper::isAllowUID($uid) : $uid;
        return self::getInternalStorage('data/users/' . $uid . DS, false);
    }

    /**
     * @return \System\ACL\IACL
     */

    public static function getACL()
    {
        if (self::$_acl == null) {
            self::$_acl = ACLHelper::getACLInstance("db", null);
        }
        return self::$_acl;
    }

    public static function getAppScreenshoots($appId)
    {
        $app = AppManager::getAppInfo($appId, false);
        if ($app) {
            $path = ASSET_PATH . 'applications/' . $app->app_id
                . '/assets/screenshoots/';
            if (is_dir($path)) {
                return self::assetToLive(
                    Utils::getDirFiles($path, $path, false, '/\.png$/i'));
            }
        }
        return null;
    }

    /**
     * Enter description here ...
     *
     * @return \System\Cache\Engine\ICacheEngine
     */
    public static function getInternalCacheManager()
    {
        if (self::$_internalCache == null) {
            self::$_internalCache = \System\Cache\CacheFactory::getInstance(true, 'Base');
            self::$_internalCache->setCacheTimeOut(0);
            //self::$_internalCache->setCachePath(self::getInternalStorage('.cache', false, true));
        }
        return self::$_internalCache;
    }

    /**
     * Enter description here ...
     *
     * @return System\Cache\Engine\ICacheEngine
     */

    public static function getCacheManager()
    {
        if (self::$_cacheManager == null) {
            self::$_cacheManager = \System\Cache\CacheFactory::getInstance();
        }
        return self::$_cacheManager;
    }

    public static function isAllow($o, $group, $access = 'view', $userid = null)
    {
        return self::getACL()->isAllow($o, $group, $access, $userid);
    }

    public static function assetToLive($asset)
    {
        if (!self::isAllowAssetToLive($asset)) {
            return null;
        }
        if (Utils::isLive($asset)) {
            return Utils::PathToLive($asset);
        }
        return self::pathToLive($asset);
    }

    protected static function pathToLive($path)
    {
        if (Strings::BeginWith($path, self::$_allowedLivePath, true)) {
            $path = Strings::Replace(self::$_allowedLivePath,
                BASE_URL . 'assets', $path);
            return $path;
        }
        throw new Exception("x");
    }

    public static function addAlowedLiveAssetPath($path)
    {
        if (!in_array($path, self::$_allowedLivePath)) {
            self::$_allowedLivePath[] = \Utils::ToDirectory($path);
        }
    }

    public static function isAllowAssetToLive($asset)
    {
        if (Strings::BeginWith($asset, 'https:')
            || Strings::BeginWith($asset, 'http:')
            || Strings::BeginWith($asset, 'ftp:')
        ) {
            return $asset;
        }
        if (!Strings::BeginWith($asset, self::$_allowedLivePath)) {
            if (CGAF_DEBUG) {
                pp($asset);
                ppd(self::$_allowedLivePath);
            }
            return false;
        }
        return true;
    }

    public static function isAllowFile($asset, $access = NULL)
    {
        $allow = array();
        $asset = Utils::ToDirectory($asset);
        if (AppManager::isAppStarted()) {
            $allow[] = AppManager::getInstance()->getLivePath();
            $allow[] = AppManager::getInstance()->getTemporaryPath();
        }
        if (Strings::EndWith($asset,
            array(
                '.manifest',
                '.min.js',
                '.min.css'
            ), true)
        ) {
            return true;
        }
        $allow[] = self::getTempPath();
        if (CGAF_DEBUG) {
            $allow[] = self::ToDirectory(SITE_PATH . 'assets/compiled/');
        }
        if (Strings::BeginWith($asset, $allow)) {
            return true;
        }
        if (Strings::Contains($asset, array(
            '.cache'
        ))
        ) {
            return true;
        }
        return AppManager::getInstance()->isallowFile($asset, $access);
    }

    public static function isShutdown()
    {
        return self::$_shutdown;
    }

    public static function RegisterAutoLoad(callable $func)
    {
        if (!in_array($func, self::$_autoLoadCallBack)) {
            self::$_autoLoadCallBack[] = $func;
        }
    }

    public static function AddNamespaceClass($prefix, $ns)
    {
        self::$_nsClass[$prefix] = $ns;
    }

    private static function _getClassInstance($className, $suffix, $args = null,
                                              $newinstance = true)
    {
        $cname = array();
        if (class_exists('AppManager', false) && AppManager::isAppStarted()) {
            $cname[] = AppManager::getInstance()->getAppName() . $className
                . $suffix;
        }
        $suffix = strtolower($suffix);
        if (CGAF_CLASS_PREFIX) {
            $cname[] = CGAF_CLASS_PREFIX . $className . $suffix;
        }
        $cname[] = $className . $suffix;
        foreach ($cname as $c) {
            if (class_exists($c, false)) {
                return $newinstance ? new $c($args) : $c;
            }
        }
        return null;
    }

    public static function getClassNameFor($classname, $namespace,
                                           $useApp = true)
    {
        if ($useApp && AppManager::isAppStarted()) {
            return AppManager::getInstance()
                ->getClassNameFor($classname, $namespace);
        }
        $search = array(
            $namespace . '\\' . $classname,
            $classname
        );
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

    public static function getClassInstance($className, $suffix, $args = null,
                                            $newinstance = true)
    {
        $ci = self::_getClassInstance($className, $suffix, $args, $newinstance);
        if (!$ci) {
            //pp($suffix);
            $cpath = self::getClassPath($suffix ? $suffix : 'system');
            if (!$cpath) {
                return false;
            }
            foreach ($cpath as $c) {
                $cf = Utils::ToDirectory(
                    $c . DS . strtolower($className) . '.class'
                    . CGAF_CLASS_EXT);
                self::Using($cf, false);
                $ci = self::_getClassInstance($className, $suffix, $args);
                if ($ci) {
                    return $ci;
                }
                $cf = Utils::ToDirectory(
                    $c . DS . strtolower($className) . CGAF_CLASS_EXT);
                self::Using($cf, false);
                $ci = self::_getClassInstance($className, $suffix, $args,
                    $newinstance);
                if ($ci) {
                    return $ci;
                }
                if (strpos($className, NSS) !== false) {
                    $cf = Utils::ToDirectory(
                        $c . DS . $className . CGAF_CLASS_EXT);
                    self::Using($cf, false);
                    $ci = self::_getClassInstance($className, $suffix, $args,
                        $newinstance);
                    if ($ci) {
                        return $ci;
                    }
                    $cf = Utils::ToDirectory(
                        $c . DS
                        . substr($className, 0,
                            strrpos($className, NSS)) . DS
                        . strtolower(
                            substr($className,
                                strrpos($className, NSS)))
                        . CGAF_CLASS_EXT);
                    self::Using($cf, false);
                    $ci = self::_getClassInstance($className, $suffix, $args,
                        $newinstance);
                    if ($ci) {
                        return $ci;
                    }
                }
            }
        }
        return $ci;
    }


    public static function toDirectory($dir, $replaceSpace = false)
    {
        if ($dir === (array)$dir) {
            $retval = array();
            foreach ($dir as $k => $v) {
                $retval[$k] = self::ToDirectory($v, $replaceSpace);
            }
            return $retval;
        }
        $dir = str_replace(array(
            NSS,
            '/',
            DS . DS
        ), DS, $dir);
        //$dir = str_replace("/", DS, $dir);
        //$dir = str_replace(DS . DS, DS, $dir);
        if (DS == "/" && $replaceSpace) {
            $dir = str_replace(" ", "\\ ", $dir);
        }
        return $dir;
    }

    private static function loadAppClass($class)
    {
        if (!$class) return false;
        $app = null;
        $cache = null;
        if (class_exists('\AppManager', false)) {
            if (\AppManager::isAppStarted()) {
                $app = \AppManager::getInstance();
                $cache = $app->getCached('classes', $class);
            }
        }

        if ($cache !== null) {
            self::Using($cache,true,false);
            return true;
        }
        return false;
    }

    private static function loadCoreClass($classname)
    {
        if (isset(self::$_internalClassCache[$classname])) {
            self::Using(self::$_internalClassCache[$classname],true,false);
            return true;
        }
        return false;
    }

    public static function loadClass($className, $throw = true)
    {
        $rClassName = NSS . trim($className, NSS);
        $className = trim(
            NSS . str_replace(array(
                '.'
            ), NSS, $className), ' ' . NSS);
        $namespaces = explode(NSS, $className);
        if (self::loadAppClass($rClassName)) {
            return true;
        } elseif (self::loadCoreClass($className)) {
            return true;
        }
        //unset($namespaces[sizeof($namespaces) - 1]);
        // the last item is the classname
        //$clname = $className;
        $oricpath = null;
        if (count($namespaces) === 1) {
            $namespaces = array_merge(array(
                'System'
            ), $namespaces);
        }
        $fns = $namespaces[0];
        $cname = array_pop($namespaces);
        if ($namespaces[0] === 'System') {
            unset($namespaces[0]);
        }
        $nspath = self::getClassPath($fns) ? self::getClassPath($fns) : array();
        if (!$nspath) {
            $nspath = self::getClassPath('system');
        }
        //$current = "";
        $fdebug = array();
        $fclass = array();
        $oricpath = implode(DS, $namespaces);
        foreach ($nspath as $p) {
            $current = "";
            foreach ($namespaces as $namepart) {
                $current .= NSS . $namepart;
                if (in_array($current, self::$loadedNamespaces)) {
                    continue;
                }
                self::$loadedNamespaces[] = $current;
                $current = str_replace('\\', DS, $current);
                $fnload = self::toDirectory($p . $current . DS . "__init.php");
                if (file_exists($fnload)) {
                    self::Using($fnload);
                }
                $fnload = $p . $current . DS . $oricpath . DS . "__init.php";
                if (file_exists($fnload)) {
                    $fclass[] = self::toDirectory($fnload);
                    self::Using($fnload);
                }
            }
            $fname = self::toDirectory($p . $oricpath . DS . $cname . '.php');
            $fdebug[] = $fname;
            if (is_file($fname)) {
                $fclass[] = $fname;
                self::Using($fname);
            }
            $fname = self::toDirectory(
                $p . $oricpath . DS . strtolower($cname) . '.php');
            $fdebug[] = $fname;
            if (is_file($fname)) {
                $fclass[] = $fname;
                self::Using($fname);
            }
        }
        if (!class_exists($rClassName, false) && interface_exists($rClassName, false)) {
            foreach (self::$_autoLoadCallBack as $func) {
                if (call_user_func_array($func, array(
                    $className
                ))
                ) {
                    break;
                }
            }
        }
        // return true if class is loaded
        if (class_exists($rClassName, false)
            || interface_exists($rClassName, false)
        ) {
            if (class_exists('\AppManager', false)) {
                if (\AppManager::isAppStarted()) {
                    $app = \AppManager::getInstance();
                    $app->putCache('classes', $rClassName, $fclass);
                }else{
                    self::$_internalClassCache[$className] = $fclass;
                }
            } else {
                self::$_internalClassCache[$className] = $fclass;
            }
            return true;
        }
        if ($throw) {
            pp($fns);
            pp($namespaces);
            pp($nspath);
            ppd($fdebug);
            throw new SystemException('Class [' . $className . '] not found');
        }
        return false;
    }

    public static function loadLibs($libName)
    {
        $libName = str_replace("/", ".", $libName);
        $libName = str_replace(DS, ".", $libName);
        if (!self::Using('Libs.' . $libName, true)) {
            self::Using("Libs.$libName.$libName");
        }
    }

    /**
     * Get database Connection
     *
     * @throws Exception
     * @return System\DB\DBConnection
     */

    public static function getDBConnection()
    {
        if (self::$_dbConnection == null) {
            self::using('System.DB');

            try {
                self::$_dbConnection = DB::Connect(self::getDBConfigs());
                self::$_dbConnection->setThrowOnError(true);
            } catch (\Exception $e) {
                if (\System::isWebContext()) {
                    \Response::Redirect(BASE_URL . '/offline.php?id=2001');
                } else {
                    throw $e;
                }
            }
        }
        return self::$_dbConnection;
    }

    /**
     * @param $o
     *
     * @return \System\DB\DBQuery
     */

    public static function getConnector($o = null)
    {
        $q = new DBQuery(self::getDBConnection());
        if ($o) {
            return $q->addTable($o);
        }
        return $q;
    }

    /**
     * @return \System\Locale\Locale
     */

    public static function getLocale()
    {
        if (AppManager::isAppStarted()) {
            return AppManager::getInstance()->getLocale();
        }
        if (self::$_locale == null) {
            self::$_locale = new Locale(
                self::getConfig("locale.locale.default", "en"),
                CGAF_PATH . DS . "locale");
        }
        return self::$_locale;
    }

    public static function _($msg, $def = null, $locale = null)
    {
        if (class_exists('AppManager', false)) {
            if (AppManager::getActiveApp() != null) {
                return AppManager::getInstance()->getLocale()
                    ->_($msg, $def, null, $locale);
            }
        }
        return self::getLocale()->_($msg, $def, null, $locale);
    }

    public static function getInternalStorage($path = null, $checkApp = true,
                                              $create = false, $mode = 0750)
    {
        if (!self::$_internalStorage) {
            self::$_internalStorage = Utils::ToDirectory(
                self::getConfig('cgaf.internalstorage',
                    CGAF_PATH . DS . 'protected' . DS));
        }
        $retval = null;
        if ($checkApp && AppManager::isAppStarted()) {
            return AppManager::getInstance()
                ->getInternalStorage($path, $create);
        } else {
            if ($create) {
                \Utils::makeDir(self::$_internalStorage . $path, $mode);
            }
            return \Utils::toDirectory(self::$_internalStorage . $path);
        }
    }
}

spl_autoload_register('CGAF::loadClass');
include 'cgaf.func.php';
?>
