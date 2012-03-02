<?php
namespace System\Models;
use System\MVC\Model;

class PersonDetail extends Model {
  public $person_id;
  public $detail_type;
  public $id;
  public $title;
  public $descr;
  public $idx;

  function __construct() {
    parent::__construct(\CGAF::getDBConnection(), 'person_detail', 'person_id,detail_type,id');
  }

  function loadByPerson($id) {
    $this->reset('person');
    $this->where('person_id=' . $this->quote($id));
    //TODO SECURE
    return $this->loadObjects();
  }

  function reset($mode = null, $id = null) {
    $this->setAlias('pm');
    parent::reset();
    switch ($mode) {
      case 'person':
        $this->clear('field');
        $this->select('pm.*,pt.type_name,pt.type_descr,pt.callback');
        $this->join('person_detail_type', 'pt', 'pm.detail_type=pt.type_id', 'left');
        $this->orderBy('pt.type_name,idx');
        break;
    }
    return $this;
  }
}