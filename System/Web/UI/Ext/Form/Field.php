<?php
namespace System\Web\UI\Ext\Form;

use System\Web\UI\Ext\Component;

class Field extends Component
{
    function __construct($xtype, $name, $label, $value, $configs = array())
    {
        $config = (array(
            "xtype" => $xtype,
            "name" => $name,
            "value" => $value,
            "fieldLabel" => $label));
        if (!$configs) {
            $configs = array();
        }
        $config = \Utils::arrayMerge($config, $configs);
        $this->removeConfig("id");
        parent::__construct($config);
    }
}