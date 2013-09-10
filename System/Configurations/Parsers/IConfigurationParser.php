<?php
namespace System\Configurations\Parsers;

use System\Configurations\IConfiguration;

interface IConfigurationParser
{
    function parseFile($f);

    function parseString($s);

    function save($fileName, IConfiguration $configs, $settings = null);
}

?>
