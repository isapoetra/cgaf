<?php
namespace System\Controllers;
use System\MVC\Controller;
class MenusController extends Controller {
	function Initialize() {
		parent::Initialize();
		$this->setModel('menus');
	}
	function Index() {
		return parent::render();
	}
}
