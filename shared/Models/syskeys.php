<?php
namespace System\Models;
use System\MVC\Model;
class SysKeys extends Model {
	/**
	 * @FieldType int
	 * @FieldExtra NOT NULL AUTO_INCREMENT
	 * var int
	 */
	public $syskey_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $app_id;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $syskey_name;
	/**
	 * @FieldLength 255
	 *
	 * @var string
	 */
	public $syskey_label;
	/**
	 * @FieldLength 1
	 *
	 * @var int
	 */
	public $syskey_type;
	/**
	 * @FieldLength 2
	 * @FieldDefaultValue PHP_EOL
	 * var string
	 */
	public $syskey_sep1;
	/**
	 * @FieldLength 2
	 * @FieldDefaultValue |
	 *
	 * @var string
	 */
	public $syskey_sep2;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(),'syskeys','syskey_id',true,\CGAF::isInstalled()===false);
	}
}