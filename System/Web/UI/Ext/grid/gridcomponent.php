<?php
namespace System\Web\UI\Ext\Grid;

use System\Web\UI\Ext\Component;

class GridComponent extends Component
{
    function __construct()
    {
        parent::__construct(array("xtype" => "grid"));
    }
}
