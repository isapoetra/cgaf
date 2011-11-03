<?php
namespace System\Models;
use System\MVC\Models\ExtModel;
use System\MVC\Model;
class recentlogModel extends ExtModel {
	public $title;
	public $descr;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'recentlog', 'id', true);
	}
}
