<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;
class RolesModel extends Model {
	public $role_id;
	public $role_name;
	public $active;
	public $app_id;
	public $role_parent;
	function __construct() {
		parent::__construct(CGAF::getDBConnection(),'roles',array('role_id','app_id'),true);
	}
	function loadSelect() {
		return $this->clear()
		->select('role_id `key`,role_name `value`,\'\' descr')
		->where($this->quoteField('app_id').'='.$this->quote(AppManager::getInstance()->getAppId()))
		->where('active=1')
		->loadAll();
	}
	function resetgrid($id = null) {
		parent::resetgrid($id);
		return $this;
	}
}