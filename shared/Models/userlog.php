<?php


namespace System\Models;
use System\ACL\ACLHelper;

use System\MVC\Model;
class UserLog extends Model {
	public $log_id;
	public $user_id;
	public $log_type;
	public $log_descr;
	public $date_created;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'user_log','log_id');
	}
	function prepareOutput($o) {
		if (is_object($o)) {
			$o->action_descr = unserialize($o->action_descr);
		}
		return parent::prepareOutput($o);
	}
	function check($mode = null) {
		$mode = $this->getCheckMode($mode);
		$this->user_id = ACLHelper::getUserId();
		return parent::check($mode);
	}
}
?>