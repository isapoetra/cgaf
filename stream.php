<?php
use System\Session\Session;

include 'cgafinit.php';
define('NO_COOKIE', true);
Session::Start();
$id = \Utils::getFileName($_SERVER['PATH_INFO'], false);
$store = Session::get('mediaStreaming');
if (isset($store[$id])) {
	ini_set('memory_limit', '32M');
	$f = $store[$id];
	if (is_file($f)) {
		\Streamer::Stream($f);
	}
}
throw new \Exception('media not found');
