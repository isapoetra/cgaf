<?php
namespace System\Models;
use System\MVC\Model;
class UserExternalModel extends Model {
	public $userid;
	public $exttype;
	public $extid;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'user_external', 'user_id,exttype');
	}
}
