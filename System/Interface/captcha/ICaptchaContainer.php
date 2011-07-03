<?php

interface ICaptchaContainer {
	function getConfig($configName,$def=null);
	function setConfig($configName,$value);
	function getConfigs();
	function getResource($name);
	function getFont($mode=null);

}

?>