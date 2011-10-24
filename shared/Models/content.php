<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;
class Content extends Model {
	/**
	 * @var unknown_type
	 * @FieldType int
	 * @FieldLength 10
	 * @FieldArg NOT NULL PRIMARY KEY AUTO_INCREMENT
	 */
	public $content_id;
	public $app_id;
	public $content_controller;
	public $position;
	public $controller;
	public $actions;
	public $params;
	public $idx;
	public $state;
	public $content_type;
	public $content_title;
	function __construct() {
		parent::__construct ( CGAF::getDBConnection (), "contents", "content_id", true );
	}
}