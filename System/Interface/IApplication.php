<?php
if (! defined("CGAF"))
	die("Restricted Access");


/**
 * Enter description here ...
 * @author Iwan Sapoetra @ Jun 18, 2011
 *
 */
interface IApplication extends
		IDBAware {
	
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

	public function getLiveData($data);

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
	 * @return IACL
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
}
?>