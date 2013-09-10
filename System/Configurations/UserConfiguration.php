<?php
namespace System\Configurations;

use System\Applications\IApplication;

class UserConfiguration extends Configuration
{
    private $_uid;
    private $_configFile = 'config.json';
    private $_path = null;

    function __construct(IApplication $appOwner, $uid, $configs = null)
    {
        parent::__construct($configs, false);
        $this->_path = \CGAF::getUserStorage($uid);
        if ($this->_path) {
            $this->_path .= $this->_configFile;
        }
    }

    /**
     * @param $configName
     * @return bool
     */
    protected function _canSetConfig($configName)
    {
        $configName = strtolower($configName);
        pp($configName);
        return false;
    }

    function __destruct()
    {
        if ($this->_path) {
            $this->Save($this->_path);
        }
    }
}

?>