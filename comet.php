<?php
define ( "CGAF_CONFIG", true );
//CGAF_APP_PATH
//uncomment this line to use default application (Web)
include "System/cgaf.php";
function realFlush()
{
	//an extra 250 byte to the browswer - Fix for IE.
	for($i=1;$i<=250;++$i)   {
		echo ' ';
	}
	flush();
	ob_flush();
}
define('CGAF_CONTEXT', 'Web');
if (CGAF::Initialize()) {
	if (!CGAF_DEBUG) {
		die("Access error");
	}
	$instance =  AppManager::getInstance();
	//while (true) {
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	$response = Response::getInstance();
	Session::Start();
	realFlush();
	Request::set('__data', true);
	Request::set('__comet', true);
	while(true) {
		try {
			CGAF::Run($instance);
			echo date("d-m-YY H:i:s:ss") ;
			$response->flush(true);
			echo "</br>";
			realFlush();
			//exit(0);
		}catch(Exception $ex) {
			die($ex->getMessage());
			Logger::Warning($ex->getMessage());
			break;
		}
		sleep(1);
	}
	echo 'Request End : '.date("d-m-YY H:i:s:ss") ;
	//}
}
?>