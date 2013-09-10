<?php
namespace System\Web\UI\Ext\Form;

use System\Web\UI\Ext\CustomComponent;

class CheckBox extends CustomComponent
{

    function __construct($config, $renderto = true)
    {
        parent::__construct("Ext.form.Checkbox", $config, null, $renderto);
    }
}

class CheckBoxEditor extends CheckBox
{

    function __construct($config)
    {
        parent::__construct($config, false);
    }
}

?>