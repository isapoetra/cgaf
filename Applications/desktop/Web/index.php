<?php
namespace System\Applications\Desktop;
use \System\Applications\WebApplication;

class WebApp extends WebApplication {
  function __construct() {
    parent::__construct(dirname(__FILE__), \CGAF::APP_ID);
  }

  public function getInternalStorage($path, $create = false) {
    return \CGAF::getInternalStorage($path, false, false);
  }
}
