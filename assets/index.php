<?php
//Just a litle script to handle assets from css or request asset start with /assets/
include dirname(__FILE__) . '/../System/cgaf.php';
if (CGAF::Initialize(true)) {
	$url = $_REQUEST['__url'];
    if (\Strings::BeginWith($url, 'assets')) {
		$f = realpath(dirname(__FILE__) . substr($url, 6));
	} else {
		$f = realpath(dirname(__FILE__) . $url);
	}
	if ($f && Strings::BeginWith($f, dirname(__FILE__))) {
        \CGAF::cacheRequest(filemtime($f),60,false,$f);
        \Streamer::Stream($f, null, false, true);
	} else {
		$fileext = substr(strrchr($url, '.'), 1);
		/*$url = substr($url, strpos($url, '/') + 1);
		$app = AppManager::getInstance();
		ppd($app->getAppId());
		$u = $app->getLiveAsset($url);*/		
		header('Content-Type: ' . FileInfo::getMimeFromExt($fileext), true);
		header("Content-Length: " . 0, true);
	}
}
?>