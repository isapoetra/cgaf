<?php
namespace System\Installer;
use System\Exceptions\SystemException;

use \CGAF;
class CGAFInstaller extends AbstractInstaller{
	function Initialize() {
		if (CGAF::isInstalled()) {
			throw new SystemException('CGAF Already installed');
		}
		return parent::Initialize();
	}
}