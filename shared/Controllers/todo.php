<?php
namespace System\Controllers;
use System\MVC\Controller;
class TodoController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'view':
		case 'index':
		case 'simple':
			return true;
		}
		return parent::isAllow($access);
	}
	function simple() {
		$m = $this->getModel();
		$rows = $m->loadObjects();
		return parent::renderView(__FUNCTION__, array(
				'rows' => $rows));
	}
	function Index() {
		$m = $this->getModel();
		$rows = $m->loadObjects();
		return parent::renderView(__FUNCTION__, array(
				'rows' => $rows));
	}
}
