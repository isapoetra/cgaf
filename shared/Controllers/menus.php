<?php
namespace System\Controllers;
use System\MVC\Controller;

/**
 * Menus Controller
 */
class Menus extends Controller {
	/**
	 * @return bool
	 */
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('menus');
		}
		return true;
	}
	/**
	 * @return string
	 */
	function Index() {
		return parent::render();
	}
}
