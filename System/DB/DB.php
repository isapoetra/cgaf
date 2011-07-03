<?php
defined("CGAF") or die("Restricted Access");
//CGAF::AddNamespaceClass("DB","System.DB.adapters");
abstract class DB {
	/**
	 *
	 * @param Array
	 * @return IDBConnection
	 */
	public static function Connect($connArgs) {
		if (!$connArgs) {
			return CGAF::getDBConnection();
		}
		$connArgs["type"] = isset($connArgs["type"]) ?$connArgs["type"] :"mysql";
		using("System.DB.adapters.".$connArgs["type"]);
		$class = CGAF_CLASS_PREFIX."DB".$connArgs['type'].'Adapter';
		if (class_exists($class,false)) {
			$retval = new $class($connArgs);
			$retval->Open();
			return $retval;
		}
		throw new Exception("Database Class [$class] Not Found");
	}
}
?>
