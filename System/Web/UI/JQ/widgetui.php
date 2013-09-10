<?php
namespace System\Web\UI\JQ;

use System\JSON\JSON;
use System\Web\JS\CGAFJS;
use System\Web\UI\Controls\WebControl;

abstract class WidgetUI extends WebControl
{
    private $_plugin;
    protected $_configs;

    function __construct($plug, $configs)
    {
        parent::__construct('div');
        $this->_plugin = $plug;
        $this->_configs = $configs;
    }

    protected function getPluginApi()
    {
        return $this->_plugin;
    }

    function prepareRender()
    {
        CGAFJS::loadPlugin($this->_plugin);
        $id = $this->getId();
        $plugapi = $this->getPluginApi();
        $configs = JSON::encodeConfig($this->_configs);
        $script = <<< EOT
$('#$id').$plugapi($configs);
EOT;
        $this->getAppOwner()->addClientScript($script);
    }
}
