<?php
namespace System\Models;
use System\MVC\Model;
class Contents extends Model {
	/**
	 *
	 * @var int
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
	 * @FieldLenth 20
	 *
	 * @var string
	 */
	public $position;
	/**
	 * @FieldLength 20
	 *
	 * @var string
	 */
	public $controller;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $actions;
	/**
	 * @FieldType text
	 *
	 * @var string
	 */
	public $params;
	/**
	 * @FieldDefaultValue 0
	 *
	 * @var int
	 */
	public $idx;
	/**
	 *
	 * @var int
	 */
	public $state;
	/**
	 *
	 * @var int
	 */
	public $content_type;
	/**
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $content_title;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $controller_app;
	function __construct() {
		parent::__construct ( \CGAF::getDBConnection (), 'contents', 'content_id,app_id', true, \CGAF::isInstalled () === false );
	}
}