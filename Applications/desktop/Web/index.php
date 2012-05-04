<?php
namespace System\Applications\Desktop;
use \System\Applications\WebApplication;
class WebApp extends WebApplication implements \IWebSocketApplicationHandler {

	function __construct() {
		parent::__construct(dirname(__FILE__), \CGAF::APP_ID);
	}
	function initWebSocket(\IWebSocketServer $server) {

	}
	function onWebSocket(\IWebSocketConnection $client, $type, $args=null) {
		return true;
	}

	public function getInternalStorage($path, $create = false) {
		return \CGAF::getInternalStorage($path, false, false);
	}
}
