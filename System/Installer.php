<?php
abstract class Installer {
	/**
	 *
	 * @param unknown_type $install
	 * @param unknown_type $basePath
	 * @return TBaseInstaller
	 */
	public static function getInstance($install,$basePath){
		if (using('System.install.'.$install)) {
				$c = CGAF_CLASS_PREFIX.$install.'Installer';
				return new $c($basePath);
		}
	}
}