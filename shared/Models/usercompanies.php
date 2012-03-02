<?php
namespace System\Models;
use System\MVC\Model;
class UserCompaniesModel extends Model {
	/**
	 * @FieldType int
	 *
	 * @var int
	 */
	public $user_id;
	/**
	 * @FieldType int
	 *
	 * @var int
	 */
	public $company_id;
	function __construct() {
		parent::__construct ( \CGAF::getDBConnection (), 'user_companies', 'user_id,company_id', false, \CGAF::isInstalled () === false );
	}
}
?>