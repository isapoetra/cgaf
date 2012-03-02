<?php
namespace System\Models;
use System\MVC\Model;
class RolePrivsModel extends Model {
	public $role_id;
	public $app_id;
	public $object_id;
	public $object_type;
	public $privs;
	function __construct($connection) {
		parent::__construct ( $connection, 'role_privs', array (
				'role_id',
				'app_id',
				'object_id',
				'object_type'
		), true, \CGAF::isInstalled () === false );
	}
}