<?php
namespace System\Cache\Engine;
interface ICacheEngine
{
    /**
     * @param $id
     * @param $group
     * @param bool $force
     * @param null $ext
     * @return mixed
     */
    function remove($id, $group, $force = true, $ext = null);

    /**
     * get cache
     * @param $id
     * @param $prefix
     * @param null $suffix
     * @param null $timeout
     * @return mixed
     */
    function get($id, $prefix, $suffix = null, $timeout = NULL);

    /**
     * put object into cache
     * @param $id
     * @param $o
     * @param $group
     * @param bool $add
     * @param null $ext
     * @return mixed
     */
    public function put($id, $o, $group, $add = false, $ext = null);


    /**
     * set cache timeout
     * @param $int
     * @return mixed
     */

    public function setCacheTimeOut($int);

    /**
     * Clear cache storage
     * @return mixed
     */
    function clear();
}
