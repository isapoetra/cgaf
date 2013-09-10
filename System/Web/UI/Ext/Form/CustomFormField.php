<?php
namespace System\Web\UI\Ext\Form;

use System\Web\UI\Ext\CustomComponent;

class CustomFormField extends CustomComponent
{

    function __construct($class, $config = null, $ignore = null)
    {
        parent::__construct($class, $config, $ignore, false);
    }
}