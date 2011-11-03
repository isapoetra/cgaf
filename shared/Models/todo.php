<?php
namespace System\Models;
use System\MVC\Models\ExtModel;
use \CGAF;
class TodoModel extends ExtModel {
	public $todo_id;
	public $todo_title;
	public $app_id;
	public $todo_state;
	function __construct() {
		parent::__construct(CGAF::getDBConnection(), 'todo', 'todo_id', true);
	}
}
