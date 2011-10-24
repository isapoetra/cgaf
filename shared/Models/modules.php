<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;
class ModulesModel extends Model {
	function __construct() {
		parent::__construct(CGAF::getDBConnection(),'modules', 'module_id',true);
	}
}