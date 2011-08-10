<?php
namespace System\MVC;
abstract class BaseController extends Controller {
	function Index() {
		
	}
	function _appList() {
		$this->Assign ( "baseurl", BASE_URL );
		return $this->getView ( null, "applist" );
	}
}
?>