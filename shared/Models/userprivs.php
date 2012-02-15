<?php
namespace System\Models;
use System\MVC\Model;
class UserPrivs extends Model {
	/**
	 *
	 * @var mixed User ID
	 *      @FieldType int
	 *      @FieldLength 10
	 */
	
	public $user_id;
	
	/**
	 *
	 * @var string application id
	 *      @FieldType varchar
	 *      @FieldLength 45
	 */
	public $app_id;
	/**
	 *
	 * @var string Object
	 *      @FieldType varchar
	 *      @FieldLength 45
	 */
	public $object_id;
	/**
	 *
	 * @var int User Priviledges
	 *      @FieldType varchar
	 *      @FieldLength 20
	 */
	public $privs;
	/**
	 *
	 * @var int @FieldType varchar
	 *      @FieldLength 45
	 */
	public $object_type;
	/**
	 *
	 * @var string Description of priviledges
	 *      @FieldType text
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
		), true );
	}
}