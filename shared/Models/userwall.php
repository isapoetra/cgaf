<?php
namespace System\Models;
use System\MVC\Model;
class UserWallModel extends Model {
	public $date_publish;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'user_wall', 'id', true);
	}
}
