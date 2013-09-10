<?php
namespace System\IO;
class ftp
{
    public $conn;

    public function __construct($url)
    {
        $this->conn = ftp_connect($url);
    }

    public function __call($func, $a)
    {
        if (strstr($func, 'ftp_') !== false && function_exists($func)) {
            array_unshift($a, $this->conn);
            return call_user_func_array($func, $a);
        } else {
            // replace with your own error handler.
            die("$func is not a valid FTP function");
        }
    }
}
