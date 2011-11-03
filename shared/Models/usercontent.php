<?php
namespace System\Models;
use System\MVC\Model;
class usercontentModel extends Model {
	public $content_id;
	public $user_id;
	public $app_id;
	public $position;
	public $controller;
	public $actions;
	public $params;
	public $idx=0;
	public $state;
	public $content_type;
	public $content_title;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'user_content', 'content_id',true);
	}
}
