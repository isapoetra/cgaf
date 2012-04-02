<?php
namespace System\Models;
use System\MVC\Model;
use CGAF;
use AppManager;
class RolesModel extends Model {
	/**
	 * @fieldtype int
	 *
	 * @var int Role ID
	 */
	public $role_id;
	/**
	 * Application ID
	 * @FieldReference applications app_id
	 * @var string Application ID
	 */
	public $app_id;
	public $role_name;
	/**
	 * @FieldType boolean
	 * @FieldAllowNull false
	 * @fielddefaultvalue 1
	 * @var boolean Flag if active
	 */
	public $active;
	public $role_parent;
	function __construct() {
		$this->_autoCreateTable = CGAF::isInstalled () === false;
		parent::__construct ( CGAF::getDBConnection (), 'roles', array (
				'role_id',
				'app_id'
		), true, \CGAF::isInstalled () === false );
	}
	function loadSelect() {
		return $this->clear ()->select ( 'role_id `key`,role_name `value`,\'\' descr' )->where ( $this->quoteField ( 'app_id' ) . '=' . $this->quote ( AppManager::getInstance ()->getAppId () ) )->where ( 'active=1' )->loadAll ();
	}
	function resetgrid($id = null) {
		parent::resetgrid ( $id );
		return $this;
	}
}