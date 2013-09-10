<?php
namespace System\Web\UI\Ext;
class ExtFunction extends ExtJS
{

    function __construct($functionName, $body, $args = null)
    {
        $arg = func_get_args();
        $arg = array_reverse($arg, true);
        array_pop($arg);
        array_pop($arg);
        $arg = array_reverse($arg, true);
        if (count($arg) > 0) {
            $arg = implode(",", $arg);
        } else {
            $arg = "";
        }
        $js = "function $functionName($arg){" . $body . "}";
        parent::__construct($js);
    }
}