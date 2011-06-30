<?php
class TCGAFInstaller extends TBaseInstaller {
	function Initialize() {
		if (CGAF::isInstalled()) {
			throw new SystemException('CGAF Already installed');
		}
		return parent::Initialize();
	}
}