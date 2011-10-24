<?php
namespace System\Models;
use System\MVC\LocaleFriendlyModel;
use \CGAF;
class VoteModel extends LocaleFriendlyModel {
	public $vote_id;
	public $app_id;
	public $vote_type;
	public $vote_title;
	public $vote_status;
	public $vote_title_id;
	function __construct() {
		parent::__construct(CGAF::getDBConnection(), 'vote', 'vote_id', 'vote_title', true);
	}
}
