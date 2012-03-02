<?php
namespace System\Controllers;
use System\MVC\Controller;

class StartMenu extends Controller {
  function __construct($appOwner) {
    parent::__construct($appOwner, "startmenu");
  }

  function isAllow($access = "view") {
    switch ($access) {
      case "index":
      case "view":
        return true;
    }
    return parent::isAllow($access);
  }

  function Index() {
    return $this->renderMenu("startmenu", null, true);
  }
}