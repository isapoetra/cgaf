<?php
namespace System\Installer;

use CGAF;
use System\Exceptions\SystemException;

class CGAFInstaller extends AbstractInstaller
{
    function Initialize()
    {
        if (CGAF::isInstalled()) {
            throw new SystemException('CGAF Already installed');
        }
        return parent::Initialize();
    }
}