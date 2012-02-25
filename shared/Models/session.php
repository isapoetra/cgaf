<?php
namespace System\Models;
use System\MVC\Model;
use CGAF;
class SessionModel extends Model {
	/**
	 * @FieldType varchar
	 * @FieldLength 250
	 * @FieldIsPrimaryKey true
	 *
	 * @var string
	 *
	 */
	public $session_id;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 * @FieldAllowNull false
	 *
	 * @var string
	 *
	 */
	public $user_id;
	/**
	 * @FieldType TIMESTAMP
	 * @FieldLength
	 * @FieldDefaultValue CURRENT_TIMESTAMP
	 *
	 * @var string
	 *
	 */
	public $date_create;
	/**
	 * @FieldType varchar
	 * @FieldLength 100
	 *
	 * @var string
	 *
	 */
	public $client_id;
	/**
	 * @FieldType DATETIME
	 * @FieldLength
	 *
	 * @var string
	 *
	 */
	public $last_access;
	function __construct() {
		parent::__construct ( CGAF::getDBConnection (), 'session', 'session_id', false, \CGAF::isInstalled () === false );
	}
}
?>