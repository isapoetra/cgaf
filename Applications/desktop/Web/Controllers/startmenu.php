<?php
class TStartMenuController extends MVCController {
	function __construct($appOwner) {
		parent::__construct ( $appOwner, "startmenu" );
	}
	function isAllow($access="view") {
		switch ($access) {
			case "index":
			case "view":
				return true;
		}
	}
	/**
	 *
	 */
	function Index() {
		return $this->renderMenu("startmenu",null,true);
	}

}