<?php
namespace System\Models;
use System\DB\DBQuery;

use System\ACL\ACLHelper;
use System\MVC\Model;
use CGAF;

class Person extends Model {
	/**
	 * @FieldIsPrimaryKey true
	 * @FieldExtra        NOT NULL AUTO_INCREMENT
	 * @var int
	 */
	public $person_id;
	/**
	 * @FieldDefaultValue -1
	 * @var int
	 */
	public $person_owner;
	/**
	 * @FieldLength 150
	 * @var string
	 */
	public $first_name;
	/**
	 * @FieldLength 150
	 * @var string
	 */
	public $middle_name;
	/**
	 * @FieldLength 150
	 * @var string
	 */
	public $last_name;
	/**
	 * @FieldType DateTime
	 * @var \DateTime
	 */
	public $birth_date;
	/**
	 * @var bool
	 */
	public $isprimary=false;
	function __construct($connection) {
		parent::__construct(CGAF::getDBConnection(), 'persons', 'person_id', false, \CGAF::isInstalled() === false);
	}
	function check($mode=null) {
		$mode = $this->getCheckMode($mode);
		switch ($mode) {
			case self::MODE_INSERT:
				$this->person_owner = ACLHelper::getUserId();
				if (empty($this->first_name)) {
					$this->addError('empty first name','first_name');
				}
				if (empty($this->last_name)) {
					$this->addError('empty last name','last_name');
				}
				$this->middle_name = $this->middle_name ? $this->middle_name : '';
				$q=new DBQuery($this->getConnection());
				$q->addTable($this->getTableName(false,false))
				->Where('first_name='.$this->quote($this->first_name))
				->Where('middle_name='.$this->quote($this->middle_name))
				->Where('last_name='.$this->quote($this->last_name))
				->Where('birth_date='.$this->quote($this->birth_date));
				$o = $q->loadObject();
				if ($o ) {
					$this->addError('name already taken, todo:: confirm for claim','first_name');
				}
		}
		return parent::check($mode);
	}
	function loadObjects($class = null, $page = -1, $rowPerPage = -1) {
		$o = parent::loadObjects(null, $page, $rowPerPage);
		if (!$class) {
			$retval = null;
			if ($o) {
				$retval = array();
				foreach ($o as $a) {
					$p = new \PersonData($this);
					$p->Assign($a);
					$retval[] = $p;
				}
			}
		}
		return $retval;
	}
	function loadObject($o=null) {
		$r	= parent::loadObject();

		if (!$r) {
			return null;
		}
		if (!$o || !($o instanceof \PersonData)) {
			$o = new \PersonData($this);
		}elseif(is_string($o)) {
			$o=new $o($this);
		}
		if (!isset($r->person_id)) {
			return $r;
		}
		$o->Assign($r);
		return $o;
	}
	function getPersonByUser($id) {
		$this
		->setAlias('p')
		->clear()
		->select("p.*");
		$this->join(
				CGAF::getDBConnection()
				->quoteTable('users', true), "u", "u.user_id=p.person_owner", 'inner', true
		);
		$this->where("p.person_owner=" . $this->quote($id));
		$this->where('p.isprimary=true');
		$pdata =$this->loadObject();
		if ($pdata && !$pdata->person_id) {
			return null;
		}
		return $pdata;
	}
}

?>