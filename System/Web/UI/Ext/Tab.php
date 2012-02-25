<?php
namespace System\Web\UI\Ext;
class TTabPanel extends Control {

  function __construct ($config) {
    parent::__construct("G.Panel");
    $this->setConfigs($config);
  }
}
class TExtTabAjax extends Control {
  protected $_baseHref;
  protected $_baseInc;
  protected $_active;

  function __construct ($baseHRef = '', $baseInc = '', $active = 0) {
    parent::__construct("G.controls.TabControl");
    $this->_itemClass["items"] = "G.controls.TabPanel";
    $this->_baseHref = $baseHRef;
    $this->_baseInc = $baseInc;
    $this->_active = $active;
    //parent::__construct($baseHRef,$baseInc,$active,$javascript,"Ext.TabPanel");
    $this->addIgnoreConfigStr(array(
      "activeTab" ,
      "serverTabId" ,
      "autoLoad" ,
      "defaults"));
    $this->setConfig("plain", true);
    $this->setConfig("defaults", array(
      "autoScroll" => true));
    //$this->setConfig("autoHeight", true);
    $this->setConfigs(array(
      "autoShow" => true ,
      "border" => false ,
      "height" => 400));
  }

  function &add ($file, $title, $key = NULL, $opturl = null) {
    $nid = "tbItem" . CGAF::genID();
    $pos = $this->getItemCount();
    if (strpos($file, "?") !== false) {
      $url = $file;
    } else {
      $url = $this->_baseHref . "&_a=" . $file;
    }
    $url .= $opturl . "&_tab=$pos";
    $autoLoad = "{url:'" . $url . "',method:'GET',scripts:true}";
    $itm = array(
      "id" => $nid ,
      "serverTabId" => $key ,
      "autoLoad" => $autoLoad);
    if (is_string($title)) {
      $itm["title"] = __($title);
    } elseif (is_array($title)) {
      $itm = Utils::arrayMerge($itm, $title);
    }
    return $this->addItem($itm, $key);
  }

  function prepareConfigItem ($configType, & $itm) {
    switch (strtolower($configType)) {
      case "items":
        foreach ($itm as $k => $v) {
          if (is_array($v)) {
            $itm["$k"]["autoHeight"] = true;
          }
        }
    }
    parent::prepareConfigItem($configType, $itm);
  }

  function getActiveTabIdx () {
    $idx = 0;
    $itm = $this->getConfig("items");
    if ($itm) {
      foreach (array_keys($itm) as $v) {
        if ($this->_active == $v) {return $idx;}
        $idx ++;
      }
    }
    return 0;
  }

  function preRender () {
    $idx = $this->getActiveTabIdx();
    $this->setConfig("activeTab", $idx);
    return parent::preRender();
  }

  function RenderExtra () {
  }

  function show ($extra = '', $return = false) {
    $retval = parent::Render(true);
    if ($extra != '') {
      Response::Write($extra);
    }
    if (! $return) {
      Response::Write($retval);
    }
    return $retval;
  }

  function showContent ($tabid) {
    $tb = $this->tabs[$tabid];
    $f = $this->baseInc . $tb[0] . ".php";
    if (is_file($f)) {
      require_once ($f);
    } else {
      dprint(__FILE__, __LINE__, E_WARNING, "file $f not Found ");
    }
  }
}
?>