<?php
namespace System\Collections;

class WebScriptCollection extends Collection implements \IRenderable
{

    function Render($return = false)
    {
        return \Convert::toString($this);
    }
}

?>