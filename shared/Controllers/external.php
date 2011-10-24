<?php
namespace System\Controllers;
use System\MVC\Controller;
class ExternalController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'addto':
		case 'view':
		case 'index':
			return true;
		}
		return parent::isAllow($access);
	}
	function share($url, $title = null) {
		return parent::render(array(
				'_a' => 'share'), array(
				'url' => $url,
				'title' => $title,
				'services' => $this->getConfig('services')), true);
	}
	function addTo() {
		return parent::render(null);
	}
}
