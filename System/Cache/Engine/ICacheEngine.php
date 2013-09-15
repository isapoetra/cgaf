<?php
namespace System\Cache\Engine;
interface ICacheEngine
{
    function remove($id, $group, $force = true, $ext = null);

    function get($id, $prefix, $suffix = null, $timeout = NULL);

    function clear();

    public function getId($o);

    public function put($id, $o, $group, $add = false, $ext = null);

    public function setCacheTimeOut($int);
}
