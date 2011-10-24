<?php
class UserPrivs extends MVCModel {
	/**
	 * @var unknown_type
	 * @FieldType int
	 * @FieldLength 10
	 */
	public $user_id;
	/**
	 * @var unknown_type
	 * @FieldType varchar
	 * @FieldLength 45
	 */
	public $app_id;
	/**
	 * @var unknown_type
	 * @FieldType varchar
	 * @FieldLength 45
	 */
	public $object_id;
	/**
	 * @var unknown_type
	 * @FieldType varchar
	 * @FieldLength 20
	 */
	public $privs;
	/**
	 * @var unknown_type
	 * @FieldType varchar
	 * @FieldLength 45
	 */
	public $object_type;
	/**
	 * @var unknown_type
	 * @FieldType text
	 */
	public $descr;
	function __construct($connection) {
		parent::__construct($connection,'user_privs',array('role_id','app_id','object_id'),true);
	}
}