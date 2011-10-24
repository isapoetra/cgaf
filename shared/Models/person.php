<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;
class PersonData extends \Object{
	public $person_id;
	public $first_name;
	public $middle_name;
	public $last_name;
	public $birth_date;
	public $email;
	public function getFullName() {
		return sprintf('%s %s %s',$this->first_name,$this->middle_name,$this->last_name);
	}
}
class Person extends Model {
	public $person_id;
	public $first_name;
	public $middle_name;
	public $last_name;
	public $birth_date;
	function __construct($connection) {
		parent::__construct(CGAF::getDBConnection(),'persons','person_id');
	}

	function getPersonByUser($id) {
		$this->setAlias('p')
		->clear()
		->select("p.*")
		->join(CGAF::getDBConnection()->quoteTable('users',true),"u","u.person_id=p.person_id",'inner',true)
		->where("u.user_id=".(int)$id);
		$o = $this->loadObject(new PersonData());
		return $o;
	}
}
?>