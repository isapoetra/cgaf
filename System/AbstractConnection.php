<?php

use System\Configurations\Configuration;

abstract class AbstractConnection implements \IConnection
{
    private $_config;
    private $_connected;

    function __construct()
    {
        $this->_config = new Configuration(null, false);
    }

    function isConnected()
    {
        return $this->_connected === true;
    }

    function setConfigs($configs)
    {
        return $this->_config->setConfigs($configs);
    }

    function setConfig($configName, $value)
    {
        return $this->_config->setConfig($configName, $value);
    }

    function getConfig($configName, $default = null)
    {
        return $this->_config->getConfig($configName, $default);
    }

    function Open()
    {
        $this->_connected = true;
        return true;
    }

    function Close()
    {
        $this->_connected = false;
        return true;
    }
}