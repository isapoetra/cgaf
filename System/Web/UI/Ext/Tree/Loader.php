<?php
namespace System\Web\UI\Ext\Tree;

use System\Web\UI\Ext\CustomComponent;

class Loader extends CustomComponent
{
    function __construct($url)
    {
        parent::__construct("G.TreeLoader", null, false);
        $this->setConfig("dataUrl", $url);
    }

}
