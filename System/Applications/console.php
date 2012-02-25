<?php
namespace System\Applications;
use System\Exceptions\SystemException;

use \System;
use \Utils;
class ConsoleApplication extends AbstractApplication {
	function __construct($appPath, $appName) {
		parent::__construct($appPath, $appName);
		if (!System::isConsole()) {
			throw new SystemException('this file not allowed running from web');
		}
		Utils::sysexec('set TERM=linux');
	}
	/* (non-PHPdoc)
	 * @see Application::getAssetPath()
	 */
	function getAssetPath($data, $prefix = null) {
		// TODO Auto-generated method stub
	}
}
?>
