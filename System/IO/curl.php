<?php
namespace System\IO;

class Curl implements \IConnection
{
    private $_remoteURL;
    private $_conn;

    function __construct($remoteurl)
    {
        $this->_remoteURL = $remoteurl;
    }

    function __destruct()
    {
        $this->Close();
    }

    private function init()
    {
        if ($this->_conn) {
            return;
        }
        $this->_conn = curl_init();
    }

    function Open()
    {
        $this->init();
        $browser_id = '	Mozilla/5.0 (Ubuntu; X11; Linux i686; rv:8.0) Gecko/20100101 Firefox/8.0';
        $opts = array(
            CURLOPT_URL => $this->_remoteURL,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $browser_id
        );
        curl_setopt_array($this->_conn, $opts);
        $result = curl_exec($this->_conn);
        return $result;
    }

    function Close()
    {
        if ($this->_conn) {
            curl_close($this->_conn);
        }
    }
}
