<?php
namespace System\Controllers;
use System\MVC\Controller;
class StatsController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'view':
			return true;
			break;
		default:
			break;
		}
		return parent::isAllow($access);
	}
}
