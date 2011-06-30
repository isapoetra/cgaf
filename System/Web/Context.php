<?php

class WebContext {
	private static $_log;

	public static function LoadClass($class) {
		
		if (substr ( $class, 0, 2 ) == CGAF_CLASS_PREFIX . "JQ") {
			return CGAF::Using ( "System.Web.UI.JQ." . substr ( $class, 2 ) );
		}
		
		if (substr ( $class, 0, 2 ) == CGAF_CLASS_PREFIX . "JExt") {
			$class = strtolower ( $class );
			return CGAF::Using ( "System.Web.UI.Ext." . substr ( $class, 3 ) );
		}
		if (substr ( strtoupper ( $class ), 0, 4 ) == CGAF_CLASS_PREFIX . "HTML") {
			$class = substr ( strtolower ( $class ), 4 );
			return CGAF::Using ( "System.Web.UI.html." . $class, false );
		}
	
	}

	public static function getLog() {
		return self::$_log;
	}

	public static function onLog($msg, $level) {

	}

	public static function Initialize() {
		using ( 'System.Web.interfaces.*' );
		Logger::onLog ( array (
				'WebContext', 
				'onLog' ) );
		CGAF::RegisterAutoLoad ( array (
				"WebContext", 
				"LoadClass" ) );
	}
}