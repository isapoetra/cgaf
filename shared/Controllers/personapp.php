<?php
namespace System\Controllers;
use System\Exceptions\SystemException;
use System\API\PublicApi;
use System\MVC\Controller;
class personappController extends Controller {
	public function isAllow($access = 'view') {
		$access = $access ? $access : 'view';
		switch ($access) {
		case 'view':
			return true;
		case 'contact':
		case 'index':
		case 'lists':
			return $this->getAppOwner()->isAuthentificated();
		}
		return parent::isAllow($access);
	}
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('persondetail');
			return true;
		}
		return false;
	}
	function parseCallback($callback, $descr) {
		if (!$callback) {
			return $callback;
		}
		//TODO parse by application
		switch ($callback) {
		case 'email':
			$retval = '<a href="mailto:' . $descr . '">send email</a>';
			break;
		case 'skype':
			$retval = PublicApi::share('skype', 'onlinestatus', $descr);
			break;
		case 'ymsgrstatus':
			$retval = PublicApi::share('yahoo', 'onlinestatus', $descr);
			break;
		default:
			$retval = null;
			if (CGAF_DEBUG) {
				throw new SystemException('unhandled contact callback ' . $callback);
			}
		}
		return $retval;
	}
	function prepareRender() {
		parent::prepareRender();
		$appOwner->clearCrumbs();
		$appOwner->addCrumbs(array(
						array(
								'title' => 'Person')));
	}
	function contact($personId = null, $asArray = false) {
		$appOwner = $this->getAppOwner();
		//
		$id = $personId ? $personId : \Request::get('id');
		$rows = $this->getModel('persondetail')->loadByPerson($id);
		if ($asArray)
			return $rows;
		foreach ($rows as $row) {
			$row->callback = $this->parseCallback($row->callback, $row->descr);
		}
		return parent::render(__FUNCTION__, array(
				'rows' => $rows));
	}
	function detail() {
		$id = \Request::get('id') . ',' . \Request::get('app_id', $this->getAppOwner()->getAppId());
		\Request::set('id', $id);
		return parent::detail();
	}
	function lists() {
		$this->setModel('personapp');
		$rows = $this->getModel()->reset()->LoadAll();
		return parent::render(__FUNCTION__, array(
				'rows' => $rows));
	}
}
