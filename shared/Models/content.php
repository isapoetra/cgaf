<?php
namespace System\Models;
use System\MVC\Model;
use CGAF;
class Content extends Model {
	/**
	 *
	 * @var unknown_type @FieldType int
	 *      @FieldLength 10
	 *      @FieldExt NOT NULL PRIMARY KEY AUTO_INCREMENT
	 */
	public $content_id;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $app_id;
	/**
	 * @FieldLength 20
	 *
	 * @var string
	 */
	public $content_controller;
	/**
	 * @FieldLength 20
	 *
	 * @var string
	 */
	public $position;
	/**
	 * @FieldLength 20
	 * @var string
	 */
	public $controller;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $actions;
	/**
	 * @FieldType text
	 * @var string
	 */
	public $params;

	/**
	 * @FieldDefaultValue 1
	 * @var int
	 */
	public $idx;
	/**
	 * @FieldDefaultValue 1
	 * @var int
	 */
	public $state;
	/**
	 * @var int
	 */
	public $content_type;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $content_title;
	function __construct() {
		parent::__construct ( CGAF::getDBConnection (), "contents", "content_id", true );
	}
}