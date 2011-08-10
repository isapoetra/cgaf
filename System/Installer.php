<?php
abstract class Installer {
	/**
	 *
	 * @param unknown_type $install
	 * @param unknown_type $basePath
	 * @return TBaseInstaller
	 */
	public static function getInstance($install,$basePath){
		$c = '\\System\\Installer\\'.$install;
		return new $c($basePath);
	}
}