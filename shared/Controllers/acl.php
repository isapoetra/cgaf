<?php
namespace System\Controllers;
use System\ACL\ACLHelper;

use System\Exceptions\SystemException;
use System\JSON\JSONResult;
use System\MVC\Controller;
use Request;
class ACLController extends Controller {
	function isAllow($access='view') {
		return \CGAF::isAllow('system', 'manage',ACLHelper::ACCESS_MANAGE);
	}
	function manage($vars = null, $newroute = null, $return = false) {
		$a = Request::get ( "_a", 'manage' );
		$ga = Request::get ( "_gridAction" );
		$id = Request::get ( 'id' );
		$m = null;
		$backAction = null;
		$links = array ();
		switch ($a) {
			case 'userroles' :
				$m = 'user_roles';
				break;
			case 'roles' :
				$m = 'roles';
				$links [] = '<a href="' . BASE_URL . '/user/manage/">' . __ ( 'manage.user', 'User' ) . '</a>';
				break;
			default :
				break;
		}
		$model = $this->getModel ( $m );
		switch ($a) {
			case 'userroles' :
				break;
		}
		$row = null;
		if ($ga) {
			$backAction = BASE_URL . '/acl/manage/?_a=' . $a;
			$a = $m . '/' . $ga;
			switch ($ga) {
				case "add" :
					$row = $model->newData ();
					break;
				case "edit" :
					$row = $model->load ( $id );
					break;
				case 'store' :
					$model->clear ()->bind ( Request::gets () );
					if ($model->store ()) {
						return new JSONResult ( 'data.stored' );
					}
					throw new SystemException ( $model->getLastError () );
			}
		}
		// ppd($model->resetGrid()->getSQL());
		return parent::render ( array (
				'_a' => $a
		), array (
				'backAction' => $backAction,
				'model' => $model,
				'links' => $links,
				'row' => $row
		) );
	}
}
?>