<?php
namespace System\Web\UI\Ext;

use Utils;

class ExtView extends Panel
{
    function __construct($obj, $configs = array())
    {
        $initConfig = array("bodyStyle" => "background-color:white,padding: 0 0 5 5", "layout" => "table", "border" => false);
        $configs = Utils::arrayMerge($initConfig, $configs);
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }
        $items = array();
        foreach (array_keys($obj) as $k) {
            $items [] = array("title" => $k);
        }
        parent::__construct($configs);
    }
}