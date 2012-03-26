<?php
namespace System\Models;
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
    $pdata = new \PersonData ($this);
    $pdata = $this->loadObject($pdata);
    $pdata->user_id = $id;
    if (!$pdata->person_id) {
    	return null;
    }
    return $pdata;
  }
}

?>