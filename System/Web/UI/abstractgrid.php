<?php
/**
 * abstractgrid.php
 * User: e1
 * Date: 3/14/12
 * Time: 7:15 AM
 */
namespace System\Web\UI;

use System\Web\UI\Controls\WebControl;

abstract class abstractGrid extends WebControl
{
    function __construct()
    {
        parent::__construct('table');
    }
}