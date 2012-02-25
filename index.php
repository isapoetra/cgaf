<?php
//define ('CGAF_DEBUG',true);
define('CGAF_CONTEXT', "Web");
include "cgafinit.php";
if (!CGAF::isInstalled()) {
	include "install.php";
}else{
	CGAF::Run();
}
?>
