<?php
namespace System\Models;
use System\MVC\Model;

class personappModel extends Model {
	public $person_id;
	public $app_id;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'person_app','person_id,app_id',true);
	}

	function reset($mode=null,$id=null) {
		$this->setAlias('pa');
		parent::reset($mode,$id);
		$this->clear('field');
		$this->select('pa.person_id,p.*');
		$this->join('persons', 'p', 'p.person_id=pa.person_id');
		return $this;
	}
}