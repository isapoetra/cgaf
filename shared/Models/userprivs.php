<?php
namespace System\Models;
use System\MVC\Model;
class UserPrivs extends Model {
	/**
	 * @FieldType int
	 * @FieldLength 10
	 *
	 * @var mixed User ID
	 */
	public $user_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 *
	 * @var string application id
	 */
	public $app_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 *
	 * @var string Object
	 */
	public $object_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 20
	 *
	 * @var int User Priviledges
	 */
	public $privs;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 *
	 * @var int
	 *
	 */
	public $object_type;
	/**
	 * @FieldType text
	 *
	 * @var string Description of priviledges
	 */
	public $descr;
	/**
	 *
	 * @param $connection IDBConnection
	 */
	function __construct($connection) {
		parent::__construct ( $connection, 'user_privs', array (
				'role_id',
				'app_id',
				'object_id'
		), true, \CGAF::isInstalled () === false );
	}
}