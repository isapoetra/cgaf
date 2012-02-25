<?php
namespace System\Controllers;
use System\Locale\Locale;
use \System\MVC\Controller;
class LocaleController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'index':
		case 'view':
		case 'select':
			return true;
			break;
		default:
			;
			break;
		}
		return parent::isAllow($access);
	}
	function select() {
		$id = \Request::get('id',null,true);
		if ($id) {
			$this->getAppOwner()->getLocale()->setLocale($id);
			\Response::Redirect();
		}
		return $this->Index();
	}
	function __construct($appOwner) {
		parent::__construct($appOwner, 'locale');
	}
	function Index() {
		$rows = $this->getAppOwner()->getLocale()->getInstalledLocale();
		//ppd($rows);
		return parent::render(null, array(
				'rows' => $rows));
	}
}
?>