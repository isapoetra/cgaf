<?php
namespace System\Controllers;
use System\Locale\Locale;
use \System\MVC\Controller;
use System\Web\UI\Items\MenuItem;
class LocaleController extends Controller {
	function isAllow($access = 'view') {
		switch (strtolower($access)) {
		case 'index':
		case 'view':
		case 'select':
		case 'selectitems':
			return true;
			break;
		default:
			;
			break;
		}
		return parent::isAllow($access);
	}
	function selectItems($o) {
		if  (!$this->isAllow('select')){
			return null;
		}
		$c = $this->getAppOwner()->getLocale()->getLocale();
		$rows = $this->getAppOwner()->getLocale()->getInstalledLocale();
		$ori = \URLHelper::getOrigin();
		$redir = $ori !==BASE_URL ? '__redir='.urlencode($ori) : '';
		$item = new MenuItem($o->menu_id,__('locale.'.$c,$c),\URLHelper::add(APP_URL,'/locale/select/?'.$redir));
		$item->addClass('locale-select '.$c);
		foreach($rows as $r) {
      if ($r==$c) continue;
			$i = new MenuItem($o->menu_id,__('locale.'.$r,$r),\URLHelper::add(APP_URL,'/locale/select/?id='.$r.'&'.$redir));
			$i->addClass('locale-select '.$r);
			$item->addChild($i);
		}
		return $item;
	}
	function select() {
		$id = \Request::get('id',null,true);
		$redir = urldecode(\Request::get('__redir',null));
		if ($id) {
			$this->getAppOwner()->getLocale()->setLocale($id);
			\Response::Redirect($redir);
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