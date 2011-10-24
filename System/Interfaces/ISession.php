<?php
if (! defined("CGAF"))
die("Restricted Access");

interface ISession {

	function Start();
	function isStarted();

	function &get($name, $default = null);
	function set($name, $value);
	function remove($varname);

	//Session Handler


	function open($savePath, $sessName);

	function read($sessID);
	function write($sessID, $sessData);
	function destroy($sessID=null);
	function gc($sessMaxLifeTime);
}
?>