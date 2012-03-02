<?php
namespace System\Models;
use System\MVC\Model;
use CGAF;
class UserRoles extends Model {
	/**
	 * @FieldType int
	 * @FieldLength 10
	 * @FieldPrimary true
	 */
	public $role_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 * @FieldPrimary true
	 */
	public $app_id;
	/**
	 * @FieldType int
	 * @FieldLength 10
	 * @FieldPrimary true
	 */
	public $user_id;
	/**
	 * @FieldType tinyint
	 * @FieldLength 1
	 */
	public $active = 1;
	function __construct($connection = null) {
		parent::__construct ( CGAF::getDBConnection (), "user_roles", array (
				'role_id',
				'app_id',
				'user_id'
		), true, \CGAF::isInstalled () === false );
	}
	function reset($mode = null, $id = null) {
		$this->setAlias ( 'ur' );
		parent::reset ();
		$this->select ( 'ur.*,u.user_name,r.role_name,r.role_parent' );
		$this->join ( 'roles', 'r', 'ur.role_id=r.role_id and ur.app_id=r.app_id' );
		$this->join ( 'users', 'u', 'ur.user_id=u.user_id' );
		return $this;
	}
	function getGridColsWidth() {
		$w = parent::getGridColsWidth ();
		$w = array_merge ( $w, array (
				'role_id' => 50,
				'user_id' => 50,
				'active' => 50,
				'user_name' => 80,
				'role_name' => 150
		) );
		return $w;
	}
	function resetgrid($id = null) {
		$this->reset ();
		$rid = \Request::get ( 'role_id' );
		$appId = \Request::get ( 'app_id' );
		if ($appId) {
			$this->setIncludeAppId ( false );
			$this->Where ( 'ur.app_id=' . $this->quote ( $appId ) );
		}
		if ($rid != null) {
			$this->where ( 'ur.role_id=' . ( int ) $rid );
		} else {
			$rid = \Request::get ( 'user_id' );
			if ($rid !== null) {
				$this->where ( 'ur.user_id=' . ( int ) $rid );
			}
		}
		return $this;
	}
}
