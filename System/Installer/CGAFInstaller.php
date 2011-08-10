<?php
namespace System\Installer;
use \CGAF;
class CGAFInstaller extends AbstractInstaller{
	function Initialize() {
		if (CGAF::isInstalled()) {
			throw new SystemException('CGAF Already installed');
		}
		return parent::Initialize();
	}
}