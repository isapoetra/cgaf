<?php
if (!defined("CGAF")) die("Restricted Access");

class WebScriptCollection extends Collection implements IRenderable
{

    function Render($return = false)
    {
        return Convert::toString($this);
    }
}

?>