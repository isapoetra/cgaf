<?php
namespace System\Web\UI\Ext;
use \Request;
use \AppManager;
class Form extends Control {
  protected $_dosql;
  protected $_action;
  protected $_winmode;
  protected $_winid;
  protected $_cancelAction;
  protected $_viewMode = false;
  protected $_defaultType = "textfield";
  protected $_submitAction = null;
  protected $_renderStandardBar;
  function __construct ($action = null, $dosql = null, $method = "POST") {
    if (! $action) {
      $action = $this->_getDefaultAction();
    }

    $this->_htmlcontrol = "form";
    parent::__construct("Ext.FormPanel");
    $this->_baseURI = $action;
    //$this->_deleteURL = $action."&dosql=$dosql";
    $this->_action = $action;
    $this->_dosql = $dosql;
    AppManager::getInstance()->renderForm = false;
    //WebResponse::loadClientScript('lookup.js','g_lookup');
    $wmode = Request::get("winMode");
    $this->_winmode = $wmode;
    $this->_winid = Request::get("winId");
    $this->_action = $action;
    $this->_dosql = $dosql;
    //$this->setConfig("frmAction", $action);
    $this->setConfig("formId", "frm{$this->id}");
    $this->setConfig("id", "$this->id");
    $this->setConfig("labelAlign", "top");
    $this->setConfig("buttonAlign", "right");
    $this->setConfig("bodyStyle", 'padding:5px;margin:5px;');
    $this->setConfig("method", $method);
    //$this->setConfig("defaults","padding:10px");
    if (! $wmode) {
      $this->setConfig("width", '100%');
      $this->setConfig("frame", true);
    }
    $this->addIgnoreConfigStr(array(
      "reader" ,
      "errorReader"));
    $this->_renderStandardBar = $this->getConfig("renderStandardBar",true);
    $this->removeConfig("renderStandardBar");
  }

  protected function _getDefaultAction ($ignore = null) {
    if (!$ignore) {
      $ignore = array('_restart');
    }else{
      $ignore[] = "_restart";
    }
    return Request::getIgnore($ignore, true);
  }

  function setSubmitAction ($value) {
    $this->_submitAction = $value;
  }

  function setViewMode ($value) {
    $this->_viewMode = $value;
  }

  function &setConfig ($name, $val = null, $ignoreexist = true, $checkMethod = false) {
    switch ($name) {
      case "renderStandardBar":
        $this->_renderStandardBar = $val;
        break;
      case "title":
        if ($this->_winmode) {
          $this->addClientScript("Ext.getCmp('" . $this->_winid . "').setTitle('" . __($val) . "')");
        } else {
          //$renderto= CGAF :: getParam('renderTo');
          //if ($renderto) {
          //	$this->addClientScript("mainPanel.getActiveTab().setTitle('" . __($val) . "')");
          //}else{
          return parent::setConfig($name, $val);
          //}
        }
        break;
      case "width":
        if ($this->_winmode) {
          $this->addClientScript("Ext.getCmp('" . $this->_winid . "').setWidth('" . __($val) . "')");
        } else {
          return parent::setConfig($name, $val);
        }
      default:
        return parent::setConfig($name, $val);
        break;
    }
    return $val;

  }

  protected function _getCloseWin () {
    $closeWin = "";
    if ($this->_winmode && $this->_winid) {
      $closeWin = "var obj = Ext.getCmp('" . $this->_winid . "');if (typeof(obj) !=='undefined')";
    }
    return $closeWin;
  }

  protected function _getActionUrl ($param = null) {
    if (is_array($param)) {
      $param = Utils::arrayImplode($param, "=", "&");
    }
    $url = $this->_action . ($this->_dosql ? "&_dosql=$this->_dosql" : "");
    $url .= "&$param";
    return $url;
  }

  protected function _getSubmitAction ($confirm = true, $param = null) {
    $this->_submitAction = \String::RemoveString($this->_submitAction, array(
      "\t" ,
      "\n" ,
      "\r"), "");
    return ($this->_submitAction ? $this->_submitAction . ";" : "") . " doSubmitForm('" . $this->id . "',{url:'" . $this->_getActionURL($param) . "'}," . ($this->_winmode ? "{success:function(form, action){" . $this->_getCloseWin() . "{obj.dataChanged=true;obj.close()}}}" : "null") . "," . ($confirm ? "true" : "false") . ")";
  }

  function preRender ($return = false, $rendertoolbar = null) {
    $retval = parent::preRender($return);
    $rendertoolbar =  $rendertoolbar ==null ? $this->_renderStandardBar : $rendertoolbar;
    if ($this->_viewMode) {return $retval;}
    if ($rendertoolbar) {
      $cancel = array(
        "text" => __("Cancel") ,
        "type" => "button" ,
        "tooltip" => array(
          "text" => __("Cancel and close this Form")));
      $cancel["handler"] = "function() {" . $this->_getCloseWin() . " {obj.dataChanged=false;obj.close()}}";
      $buttons = array();
      if ($this->_winmode) {
        $buttons[] = $cancel;
      }
      $buttons[] = array(
        "text" => __("Reset") ,
        "handler" => "function(frm,e) {if (!G.IsNull(obj)) obj.getForm().reset()}" ,
        "tooltip" => array(
          "text" => __("Reset this form to original value")));
      /*$this->_submitAction = str_replace("\t", "", $this->_submitAction);
        $this->_submitAction = str_ireplace("\n", "", $this->_submitAction);
        $this->_submitAction = str_replace("\r", "", $this->_submitAction);*/
      $submitAction = $this->_getSubmitAction();
      $buttons[] = array(
        "text" => __("Submit") ,
        "tooltip" => array(
          "text" => __("Submit this Form")) ,
        "handler" => "function(frm,e) { $submitAction }");
      $this->setConfig("buttons", $buttons);
    }
    //ppd($buttons);
    $this->setConfig('waitMsg', "Submit data in progress");
    if (! isset($this->_config["items"])) {
      $this->_config["items"] = array();
    }
    if (count($this->_config["items"]) == 0) {
      $this->addItem(array(
        "xtype" => "hidden" ,
        "id" => CGAF::genID()), false);
    }
    //ppd($this);
    return $retval;
  }

  function prepareItems (& $arrItem) {
    //$remove= array ();
    $retval = array();
    foreach ($arrItem as $v) {
      if (is_array($v)) {
        if (! isset($v["xtype"]) && ! isset($v["items"]) && ! isset($v["inputType"])) {
          $v["xtype"] = $this->_defaultType;
        }
        if ((isset($v["xtype"]) && $v["xtype"] == null) || (isset($v["xtype"]) && isset($v["inputType"]))) {
          unset($v["xtype"]);
        }
        if (isset($v["value"]) && $v["value"] === null) {
          unset($v["value"]);
        }
        if (isset($v["items"])) {
          $v["items"] = $this->prepareItems($v["items"]);
        }
      }
      if ($v !== null) {
        $retval[] = $v;
      }
    }
    return $retval;
  }

  function &addItem ($obj, $multi = false) {
    if ($multi) {
      $obj = $this->prepareItems($obj);
      foreach ($obj as $v) {
        parent::addItem($v);
      }
      return $obj;
    } else {
      return parent::addItem($obj);
    }
  }
}
class TExtFormGrid extends Form {

  function Render ($return = false, & $handle = false) {
  }
}



?>