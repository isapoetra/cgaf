<?php
namespace System\Applications;
use \System\DB\IDBAware;

/**
 * Core Application Interface
 */
interface IApplication extends IDBAware {
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
   * @param mixed  $def
   */
  function getUserConfig($configName, $def = null);

  function setUserConfig($configName, $value = null, $uid = null);

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
   * @param string  $path
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
   * @return Authin
   *
   */
  public function getAuthInfo();

  public function getConfig($string1, $def = null);

  function addClientAsset($assetName, $group = null);

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

  public function getClassNameFor($base, $suffix, $ns=null);

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
}
