<?php


namespace System\Applications;

use System\Applications\WebApplication;
use System\MVC\MVCHelper;

class StaticWebApplication extends WebApplication
{
    private $_fileExt;

    function __construct($appPath, $appName, $fileExt)
    {
        parent::__construct($appPath, $appName);
        $this->_fileExt = $fileExt;
    }

    function Initialize()
    {
        if (parent::Initialize()) {
            return true;
        }
    }

}