<?php
namespace System\Configurations;
interface IConfiguration extends IConfigurable
{
    public function setConfigs($configs);

    function getConfigs($configName = null, $default = null);

    public function Merge($_configs);

    public function Save($fileName = null);

    public function loadFile($fileName);

}

?>