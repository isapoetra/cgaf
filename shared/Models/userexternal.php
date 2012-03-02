<?php
namespace System\Models;
use System\MVC\Model;
class UserExternalModel extends Model {
	/**
	 * @FieldType int
	 * @var unknown_type
	 */
	public $userid;
	/**
	 * @fieldType varchar
	 * @fieldlength 50
	 * @var unknown_type
	 */
	public $exttype;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 * @var unknown_type
	 */
	public $extid;
	function __construct() {
		parent::__construct ( \CGAF::getDBConnection (), 'user_external', 'user_id,exttype', false, \CGAF::isInstalled () === false );
	}
}
