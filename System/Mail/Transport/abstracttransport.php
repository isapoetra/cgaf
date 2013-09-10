<?php
namespace System\Mail\Transport;

use System\Mail\MailObject;

abstract class AbstractTransport
{
    private $_base = 'common';
    protected $_connected = false;
    private $_connection;

    function init($base = null)
    {
        $this->_base = $base;
    }

    function connect()
    {
        if ($this->_connected) {
            return;
        }
        $this->_connection = @fsockopen($host, // the host of the server
            $port, // the port to use
            $errno, // error number if any
            $errstr, // error message if any
            $tval); // give up after ? secs
        // verify we connected properly
        if (empty($this->_connection)) {
            return false;
        }
        if (substr(PHP_OS, 0, 3) != "WIN")
            socket_set_timeout($this->smtp_conn, $tval, 0);

        // get any announcement
        $announce = $this->readServer();

        \Logger::info("SERVER Response :" . $announce);
        $this->_connected = true;
        return true;
    }

    abstract function send(MailObject $o);

    function getConfigs($configName = null)
    {
        return \MailHelper::getConfigs($this->_base . ($configName ? '.' . $configName : ''));
    }

    function getConfig($configName, $default = null)
    {
        return \MailHelper::getConfig($this->_base . '.' . $configName, $default);
    }

    protected function readServer()
    {
        $data = "";
        while (!feof($this->_connection)) {
            $str = @fgets($this->_connection, 515);
            $data .= $str;
            // if 4th character is a space, we are done reading, break the loop
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $data;
    }
}