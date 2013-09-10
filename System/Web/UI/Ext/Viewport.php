<?php
class TExtRegionViewPort extends JExtComponent
{
    function __construct($configs = null, $region = "center")
    {
        $defConfig = array("region" => $region);
        $configs = Utils::arrayMerge($defConfig, $configs);
        parent::__construct($configs);
    }

    function handleContentClick($m, $a)
    {
    }
}

class TExtViewPort extends JExtControl
{
    function __construct($configs = null)
    {
        parent::__construct("Ext.Viewport");
        $this->setConfigs($configs);
    }

    function setTools($tools)
    {
        $this->setConfig("tools", $tools, false);
    }

    function add(TExtRegionViewPort $region)
    {
        parent::addItem($region);
    }
}

?>