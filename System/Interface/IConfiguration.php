<?php
if (!defined("CGAF") ) die("Restricted Access");

interface IConfiguration {

	public function setConfigs ($configs);

	public function getConfig ($configName, $default = null);

	public function Merge ($_configs);
	public function setConfig ($configName, $value = null);
	public function Save($fileName=null);
	 
}
?>