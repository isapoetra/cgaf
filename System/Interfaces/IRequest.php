<?php
defined("CGAF") or die("Restricted Access");
interface IRequest
{
    public function get($varname, $default = null, $secure = true, $place = null);

    public function gets($place = null, $secure = true);

    public function secureVar($var);
}

?>