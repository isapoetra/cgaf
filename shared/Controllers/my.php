<?php
namespace System\Controllers;
use System\ACL\ACLHelper;
use System\MVC\Controller;
class My extends Controller {
	function __construct($appOwner) {
		parent::__construct ( $appOwner, 'my' );
	}
	function isAllow($access = 'view') {
		switch ($access) {
			case 'view' :
			case ACLHelper::ACCESS_VIEW :
			case 'config' :
				return true;
		}
		return parent::isAllow ( $access );
	}
	function config() {
		$configs  = \Request::gets(null,false);
		$conf =array();
		foreach($configs as $k=>$v) {
			if ((substr($k,0,2)=='__') || strtolower($k)==='cgafsess') {
				 continue;
			}
			$conf[$k] = $v;
		}

		$c = $this->getAppOwner()->setUserConfig($conf);
		return true;
	}
}
?>