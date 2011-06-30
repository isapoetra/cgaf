<?php
using ( "System.AppModel.MVC.MVCController" );
abstract class BaseController extends MVCController {
	function Index() {
		
	}
	function _appList() {
		$this->Assign ( "baseurl", BASE_URL );
		return $this->getView ( null, "applist" );
	}
}
?>