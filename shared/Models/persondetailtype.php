<?php
/**
 * User: Iwan Sapoetra
 * Date: 04/03/12
 * Time: 20:38
 */
namespace System\Models;
use \System\MVC\Model;

class PersonDetailType extends Model {
  /**
   * @FieldType  int
   * @FieldExtra NOT NULL AUTO_INCREMENT
   * @var int
   */
  public $type_id;
  /**
   * @FieldIsAllowNull false
   * @FieldLength      50
   * @var string
   */
  public $type_name;
  /**
   * @FieldIsAllowNull false
   * @FieldLength      150
   * @var string
   */
  public $type_descr;
  /**
   * @FieldIsAllowNull false
   * @FieldLength      150
   * @var string
   */
  public $callback;

  function __construct() {
    parent::__construct(\CGAF::getDBConnection(), 'person_detail_type', 'type_id', false,true);
  }
}
