<?php

/**
 *
 */
interface IWebApplication extends IApplication {
	function addClientAsset($assetName, $group = null);
}	

?>