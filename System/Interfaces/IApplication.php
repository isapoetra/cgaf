<?php
interface IApplication extends System\DB\IDBAware {
	/**
	 * Run Application
	 */
	public function Run();

	public function getContent($position = null);

	public function getAppPath();

	public function Authenticate();

	public function isAuthentificated();

	public function isInitialized();

	public function Initialize();
	/**
	 *
	 * Enter description here ...
	 * @param string $data
	 * @deprecated use IApplication->getLiveAsset
	 */
	public function getLiveData($data);
	/**
	 *
	 * Enter description here ...
	 * @param string $data
	 */
	public function getLiveAsset($data);
	/**
	 * @return String
	 * @deprecated
	 */
	function getSharedPath();

	function Log($cat, $msg, $success);

	function Shutdown();

	/**
	 *
	 * @return boolean
	 */
	function Install();

	/**
	 * @return CacheManager
	 */
	function getCacheManager();

	/**
	 * @return System\ACL\IACL
	 */
	function getACL();

	/**
	 *
	 * @return IAuthentificator
	 */
	function getAuthentificator();
	/**
	 *
	 * get user configuration
	 * @param string $configName
	 * @param mixed $def
	 */
	function getUserConfig($configName, $def=null);
	function setUserConfig($configName, $value);
	function getAppId();

	/**
	 *
	 * Enter description here ...
	 * @return \\System\\Locale\\Locale
	 */
	function getLocale();
}