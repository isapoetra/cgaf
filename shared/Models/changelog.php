<?php
/**
 * User: Iwan Sapoetra
 * Date: 08/03/12
 * Time: 12:35
 */
namespace System\Models;
use \System\MVC\Model;

class ChangeLog extends Model {
  function __construct() {
    parent::__construct(\CGAF::getDBConnection(),'changelog','changelog_id,app_id');
  }
}

