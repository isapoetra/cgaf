<?php
namespace System\Controllers;
use System\MVC\Controller;
use \Request;
class UserRolesController extends Controller {
	function Initialize() {
		parent::Initialize ();
		$this->setModel ( 'user_roles' );
	}
	function manage($vars = null, $newroute = null, $return = false) {
		$a = $this->getManageAction ();
		if (! $a) {
			$id = Request::get ( 'user_id' );
			if ($id !== null) {
				$vars ['columns'] = array (
						'role_id',
						'role_name'
				);
				$vars ['autogenerategridcolumn'] = false;
				$vars ['gridNavigatorConfig'] = array (
						'add' => true,
						'del' => true,
						'edit' => false
				);
			}
		}
		return parent::manage ( $vars, $newroute, $return );
	}
	function del() {
		$o = $this->getModel ()->load ( Request::get ( 'id' ) );
		ppd ( $o );
	}
}
