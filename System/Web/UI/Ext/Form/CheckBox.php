<?php
class TExtCheckBox extends JExtCustom {

  function __construct ($config, $renderto = true) {
    parent::__construct("Ext.form.Checkbox", $config, null, $renderto);
  }
}
class TExtCheckBoxEditor extends TExtCheckBox {

  function __construct ($config) {
    parent::__construct($config, false);
  }
}
?>