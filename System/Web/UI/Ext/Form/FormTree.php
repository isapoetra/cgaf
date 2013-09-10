<?php
namespace System\Web\UI\Ext\Form;

use System\Web\UI\Ext\Tree;

class FormTree extends Tree
{

    function __construct($configs)
    {
        parent::__construct($configs);
        $this->removeConfig("renderTo");
    }

}