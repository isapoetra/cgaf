<?php
namespace System\Controllers;
use System\MVC\Controller;
class UIController extends Controller {
	function isAllow($access = 'view') {
		switch (strtolower($access)) {
		case 'index':
		case 'view':
		case 'shcs':
			return true;
		}
		return parent::isAllow($access);
	}
	function shcs() {
		return $this->render(array(
						'_a' => 'index',
						'_c' => 'home'));
	}
}
