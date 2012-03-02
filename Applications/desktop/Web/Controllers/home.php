<?php
namespace System\Controllers;
use System\Web\JS\CGAFJS;

class CGAFHomeController extends Home {
  function getControllerName() {
    return 'Home';
  }

  function initAction($action, &$params) {
    switch ($action) {
      case 'index':
        //CGAFJS::loadPlugin('jScrollPane/jquery.jscrollpane',true);
        //CGAFJS::loadPlugin('jScrollPane/style/jquery.jscrollpane.css',true);
        //CGAFJS::loadPlugin('jScrollPane/themes/lozenge/style/jquery.jscrollpane.lozenge.css',true);
        //$this->addClientAsset('http://cdn.jquerytools.org/1.2.6/all/jquery.tools.min.js');
        break;
      default:
        ;
        break;
    }
    return parent::initAction($action, $params);
  }
}

?>
