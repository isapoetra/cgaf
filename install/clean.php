<?php

define("SITE_PATH", dirname(__FILE__));
define("CGAF_CONFIG", true);
//Uncomment this line to disabled debugmode
define("CGAF_DEBUG", true);
//uncomment this line to use default application (Web)
include "../System/cgaf.php";
if (CGAF::Initialize() ) {
	if (CGAF_CONTEXT ==='Console') {
		$app = AppManager::getInstance();
		$app->clean();
	}
} else {
	if (CGAF_CONTEXT ==='Web') {
		die("please run from console");
	}else{
		die("unable to initialize framework");
	}
}
?>