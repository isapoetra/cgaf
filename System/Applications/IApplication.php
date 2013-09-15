<?php
namespace System\Applications;

use System\DB\IDBAware;

/**
 * Core Application Interface
 */
interface IApplication extends IDBAware
{
    /**
     * Run Application
     */
    public function Run();

    /**
     * Get Applicaton content
     *
     * @param string $position
     */
    public function getContent($position = null);

    /**
     * Get Application Path
     *
     * @return string
     */
    public function getAppPath();

    /**
     * Authentification request
     *
     * @return boolean
     */
    public function Authenticate();

    /**
     * Check if user is authentificated
     *
     * @return boolean
     */
    public function isAuthentificated();

    /**
     * Check if application is initialized
     *
     * @return boolean
     */
    public function isInitialized();

    /**
     * @abstract
     * @return boolean
     */
    public function Initialize();

    /**
     *
     * Enter description here ...
     *
     * @param string $data
     * @deprecated use IApplication->getLiveAsset
     */
    public function getLiveData($data);

    /**
     *
     * Enter description here ...
     *
     * @param string $data
     */
    public function getLiveAsset($data);

    /**
     * @return String
     * @deprecated
     */
    function getSharedPath();

    /**
     * @abstract
     * @param $cat
     * @param $msg
     * @param $success
     */
    function Log($cat, $msg, $success);

    /**
     * @abstract
     *
     */
    function Shutdown();

    /**
     *
     * @return boolean
     */
    function Install();

    /**
     * @return \System\Cache\Engine\ICacheEngine
     */
    function getCacheManager();

    /**
     * @return \System\ACL\IACL
     */
    function getACL();

    /**
     *
     * @return \System\IAuthentificator
     */
    function getAuthentificator();

    /**
     *
     * get user configuration
     *
     * @param string $configName
     * @param mixed $def
     */
    function getUserConfig($configName, $def = null);

    function setUserConfig($configName, $value = null, $uid = null);

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    function getConfigs($key, $default=null);

    /**
     * @return mixed
     */
    function getAppId();

    /**
     *
     * Enter description here ...
     *
     * @return \System\Locale\Locale
     */
    function getLocale();

    /**
     *
     * Internal storage path
     *
     * @param string $path
     * @param boolean $create
     */
    function getInternalStorage($path, $create = false);

    /**
     * Handle application error
     *
     * @param \Exception $ex
     */
    function handleError(\Exception $ex);

    function unhandledNameSpace($namespace);

    /**
     * @return \System\Auth\AuthResult
     *
     */
    public function getAuthInfo();

    public function getConfig($string1, $def = null);

    function addClientAsset($assetName, $group = null, $type = null);

    function getAppInfo();

    /**
     * @abstract
     * @return \System\Cache\Engine\ICacheEngine
     */
    public function getInternalCache();

    /**
     *
     * @param bool $sessionBased
     * @return string
     */
    function getLivePath($sessionBased = false);

    public function getClassNameFor($base, $suffix, $ns = null);

    public function setAppInfo($appInfo);

    public function getAppName();

    /**
     * @abstract
     * @return bool
     */
    public function Uninstall();

    /**
     * @abstract
     * @return \System\Configurations\IConfiguration
     */
    public function getConfigInstance();

    public function getClassPath();

    //Asset Related
    function getAsset($data, $prefix = null);

    public function getTemporaryPath();

    /**
     * perform application check
     *
     * @abstract
     */
    public function performCheck();

    /**
     * @return string
     */
    function getAssetCachePath();

    /**
     * @return bool
     */
    function isInstalled();

    /**
     * @param $id
     * @param $group
     * @param string $access
     * @param mixed $user
     * @return bool
     */
    function isAllow($id, $group, $access = 'view', $user = null);

    /**
     * @param $file
     * @param string $access
     * @return mixed
     */
    function isAllowFile($file, $access = 'view');

    /**
     * @param null $model
     * @param bool $newInstance
     * @return \System\MVC\Model
     */
    function getModel($model = null, $newInstance = false);

    /**
     * @param string $view
     * @param string $a
     * @param mixed $args
     * @param mixed $controller
     * @return mixed
     */
    function renderView($view, $a = null, $args = null, $controller = null);

    /**
     * @param null $controllerName
     * @param bool $throw
     * @throws \Exception
     * @return mixed
     */
    function getController($controllerName = null, $throw = true);

    /**
     * @param $action
     * @param null $descr
     * @param null $uid
     * @return void
     */
    function LogUserAction($action, $descr = null, $uid = null);

    /**
     * Reset Application state to factory default
     * @return mixed
     */
    function Reset();

    /**
     * @param $type
     * @param $id
     * @param $value
     * @return mixed
     */
    function putCache($type, $id, $value);

    /**
     * @param $type
     * @param $id
     * @param null $default
     * @return mixed
     */
    function getCached($type, $id, $default = null);


}
