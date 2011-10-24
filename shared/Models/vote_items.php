<?php
namespace System\Models;
use System\MVC\Model;

class Vote_ItemsModel extends Model {
	public $vote_id;
	public $vote_item_id;
	public $vote_title;
	public $vote_descr;
	public $vote_result;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(),'vote_items','vote_id,vote_item_id');
	}
}