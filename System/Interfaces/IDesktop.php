<?php

use System\Applications\IApplication;

interface IDesktop extends IApplication
{
    /**
     * @return TMenuItem
     */
    function getMainMenu();
}

?>
