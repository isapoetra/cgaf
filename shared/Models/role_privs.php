<?php
class RolePrivsModel extends MVCModel {
	public $role_id;
	public $app_id;
	public $object_id;
	public $privs;
	public $object_type;
	function __construct($connection) {
		parent::__construct($connection,'role_privs',array('role_id','app_id','object_id'),true);
	}
}