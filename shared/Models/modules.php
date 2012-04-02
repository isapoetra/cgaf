<?php
namespace System\Models;
use System\MVC\Model;
use CGAF;
class ModulesModel extends Model {
	/**
	 * @FieldExtra AUTO_INCREMENT
	 *
	 * @var int
	 */
	public $mod_id;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $app_id;
	/**
	 * @FieldLength 70
	 *
	 * @var string
	 */
	public $mod_name;
	/**
	 * @FieldLength 10
	 *
	 * @var string
	 */
	public $mod_version;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $mod_ui_name;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $mod_ui_icon;
	/**
	 * @FieldDefaultValue 1
	 *
	 * @var int
	 */
	public $mod_state;
	/**
	 * @FieldLength 255
	 *
	 * @var string
	 */
	public $mod_description;
	/**
	 * @FieldLength 20
	 *
	 * @var string
	 */
	public $mod_default_position;
	/**
	 * @FieldDefaultValue 1
	 *
	 * @var int
	 */
	public $mod_order;
	/**
	 * @FieldLength 250
	 *
	 * @var string
	 */
	public $mod_dir;
	/**
	 * @FieldLength 100
	 *
	 * @var string
	 */
	public $mod_class_name;
	/**
	 * @FieldType tinyint
	 * @FieldDefaultValue 1
	 *
	 * @var int
	 */
	public $mod_ui_active;
	/**
	 *
	 * @var int
	 */
	public $mod_ui_order;
	/**
	 * @FieldDefaultValue 1
	 * @FieldType tinyint
	 *
	 * @var unknown_type
	 */
	public $mod_active;
	/**
	 * @FieldLength 20
	 *
	 * @var string
	 */
	public $mod_controller;
	function __construct() {
		parent::__construct ( CGAF::getDBConnection (), 'modules', 'mod_id', true, \CGAF::isInstalled () === false );
	}
}