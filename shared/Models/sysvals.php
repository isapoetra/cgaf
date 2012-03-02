<?php
namespace System\Models;
use System\MVC\Model;
class SysVals extends Model {
	/**
	 * @FieldType int
	 * @FieldLength 11
	 *
	 * @var int
	 */
	public $sysval_id;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 *
	 * @var int
	 */
	public $sysval_key_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $sysval_title;
	/**
	 * @FieldType text
	 *
	 * @var string
	 */
	public $sysval_value;
	function __construct() {
		parent::__construct ( \CGAF::getDBConnection (), 'sysvals', 'sysval_id', false, \CGAF::isInstalled () === false );
	}
}