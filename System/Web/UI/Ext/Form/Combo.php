<?php
namespace System\Web\UI\Ext\Form;
use System\JSON\JSON;

use System\Web\UI\Ext\CustomComponent;
use System\Exceptions\SystemException;
class Combo extends CustomComponent {

  function __construct ($name, $value, $data, $caption = null, $displayField = 'text', $valueField = 'value') {
    parent::__construct("Ext.form.ComboBox");
    $this->addIgnoreConfigStr(array(
      "select" , 
      "valid" , 
      "collapse"));
    $this->setConfig(array(
      "name" => $name , 
      "hiddenName" => $name , 
      "mode" => 'local' , 
      "fieldLabel" => ($caption ? __($caption) : __($name)) , 
      "emptyText" => __('Select') . '...' , 
      "displayField" => $displayField , 
      "valueField" => $valueField , 
      "forceSelection" => true , 
      "triggerAction" => 'all' , 
      "value" => $value));
    if ($data) {
      $this->setConfig("store", "new Ext.data.SimpleStore({fields:['$valueField','$displayField'],data:" . JSON::encodeSimple($data) . "})");
    } else {
      throw new SystemException("Invalid Data for object " . __CLASS__);
    }
  }

  function setName ($value) {
    return $this->setConfig("name", $value);
  }
}
class ComboField extends Field {

  function __construct ($name, $value, $data, $caption = null, $displayField = 'text', $valueField = 'value', $configs = array()) {
    $displayField = $displayField ? $displayField : "text";
    $valueField = $valueField ? $valueField : 'value';
    $config = (array(
      "xtype" => 'gcombobox' , 
      "hiddenName" => $name , 
      "mode" => 'local' , 
      "fieldLabel" => ($caption ? __($caption) : __($name)) , 
      "emptyText" => __('Select') . '...' , 
      "displayField" => $displayField , 
      "valueField" => $valueField , 
      "value" => $value , 
      "selectOnFocus" => true));
    if (($data && is_array($data)) || gettype($data) == "array") {
      $config["store"] = "new Ext.data.SimpleStore({fields:['$valueField','$displayField'],data:" . JSON::encodeSimple($data, "\"") . "})";
    } elseif (is_string($data)) {
      //url based;
      $config["mode"] = 'remote';
      $config["store"] = "new Ext.data.JsonStore({url:'$data',fields:['$valueField','$displayField'],data:" . JSON::encodeSimple($data) . "})";
      $config["triggerAction"] = "all";
    }
    $this->addIgnoreConfigStr(array(
      "initComponent" , 
      "select" , 
      "valid" , 
      "collapse" , 
      "onSelect" , 
      "onClick" , 
      "onValid" , 
      "onbeforeselect"));
    $config = array_merge($config, $configs);
    parent::__construct('combo', $name, $caption, $value, $config);
    $this->removeConfig("renderTo");
    $this->removeConfig("id");
    //		/ppd($this);
  }

  function setName ($value) {
    return $this->setConfig("name", $value);
  }
}
class ComboBox extends ComboField {

  function Initialize () {
    $this->_class = "Ext.form.ComboBox";
    $this->removeConfig("renderTo");
    $this->removeConfig("xtype");
  }
}
?>