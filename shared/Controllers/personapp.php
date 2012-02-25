<?php
namespace System\Controllers;
use System\Exceptions\SystemException;
use System\API\PublicApi;
use System\MVC\Controller;
class personappController extends Controller {
	public function isAllow($access = 'view') {
		$access = $access ? $access : 'view';
		switch ($access) {
			case 'view' :
				return true;
			case 'contact' :
			case 'index' :
			case 'lists' :
				return $this->getAppOwner ()->isAuthentificated ();
		}
		return parent::isAllow ( $access );
	}
	function Initialize() {
		if (parent::Initialize ()) {
			$this->setModel ( 'persondetail' );
			return true;
		}
		return false;
	}
	function prepareRender() {
		parent::prepareRender ();
		$this->getAppOwner ()->clearCrumbs ();
		$this->getAppOwner ()->addCrumbs ( array (
				array (
						'title' => 'Person'
				)
		) );
	}
	function contact($personId = null, $asArray = false) {
		$appOwner = $this->getAppOwner ();
		//
		$id = $personId ? $personId : \Request::get ( 'id' );
		$rows = $this->getModel ( 'persondetail' )->loadByPerson ( $id );
		if ($asArray)
			return $rows;
		foreach ( $rows as $row ) {
			$row->callback = \UserInfo::parseCallback ( $row->callback, $row->descr );
		}
		return parent::render ( __FUNCTION__, array (
				'rows' => $rows
		) );
	}
	function detail($args = null, $return = null) {
		$id = \Request::get ( 'id' ) . ',' . \Request::get ( 'app_id', $this->getAppOwner ()->getAppId () );
		\Request::set ( 'id', $id );
		return parent::detail ();
	}
	function lists() {
		$this->setModel ( 'personapp' );
		$rows = $this->getModel ()->reset ()->LoadAll ();
		return parent::render ( __FUNCTION__, array (
				'rows' => $rows
		) );
	}
}
