<?php
namespace System\Models;
use System\MVC\Model;

class CompaniesModel extends Model{
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'companies','company_id');
	}
}