<?php
namespace System\Controllers;
use System\MVC\Controller;

class SystemController extends Controller {
	function manage() {
		return parent::renderMenu($this->getControllerName().'-manage');
	}
}