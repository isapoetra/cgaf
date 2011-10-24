<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;
class SessionModel extends Model {
	/**
	 *
	 * @var string
	 * @FieldType varchar
	 * @FieldLength 250
	 * @FieldIsPrimaryKey true
	 */
	public $session_id;

	/**
	 *
	 * @var string
	 * @FieldType int
	 * @FieldLength 11
	 * @FieldAllowNull false
	 */
	public $user_id;
	/**
	 *
	 * @var string
	 * @FieldType DATETIME
	 * @FieldLength
	 * @FieldDefaultValue CURRENT_TIMESTAMP
	 */
	public $date_create;
	/**
	 *
	 * @var string
	 * @FieldType varchar
	 * @FieldLength 100
	 */
	public $client_id;
	/**
	 *
	 * @var string
	 * @FieldType DATETIME
	 * @FieldLength
	 */
	public $last_access;
	function __construct() {
		parent::__construct(CGAF::getDBConnection(),'session','session_id',false);
	}
}
?>