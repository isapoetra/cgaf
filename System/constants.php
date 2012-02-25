<?php

class Constants {
	const ERR_0 = 'Under maintenance';
	const ERR_2001 = 'Database Offline';
	private static $_error = array (
			0 => self::ERR_0, 
			2001 => self::ERR_2001 
	);
	function get($id) {
		if (isset ( self::$_error [$id] )) {
			return self::$_error [$id];
		}
		return self::$_error [0];
	}
}

?>