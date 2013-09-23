<?php
namespace System\Cache;

use CGAF;

class CacheFactory
{
    private static $_instance;

    /**
     *
     * Get instance of cache engine
     * @param boolean $create
     * @param string $cacheEngine
     * @return \System\Cache\Engine\ICacheEngine
     */
    static function getInstance($create = false, $cacheEngine = null)
    {
        if ($create || self::$_instance == null) {
            $class = '\\System\\Cache\\Engine\\' . ($cacheEngine ? $cacheEngine : CGAF::getConfig("cache.engine", "Base"));
            $c = new $class();
            if (self::$_instance == null) {
                self::$_instance = $c;
            }
            return $c;
        }
        return self::$_instance;
    }

    static function get($id, $prefix, $suffix = null, $timeout = NULL)
    {
        return self::getInstance()->get($id, $prefix, $suffix, $timeout);
    }

    /**
     * @param $s
     * @param $id
     * @param null $ext
     * @return mixed
     * @deprecated
     */
    static function putString($s, $id, $ext = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return self::getInstance()->putString($s, $id, $ext);
    }

    static function getId($o)
    {
        return self::getInstance()->getId($o);
    }

    /**
     * @param string $fname
     * @return mixed
     * @deprecated
     */
    static function isCacheValid($fname)
    {
        $id = self::getId($fname, \Utils::getFileExt($fname));

        /** @noinspection PhpUndefinedMethodInspection */
        return self::getInstance()->isCacheValid($id);
    }

    /***
     * @param $fname
     * @param null $callback
     * @return mixed
     * @deprecated
     */
    static function putFile($fname, $callback = null)
    {

        /** @noinspection PhpUndefinedMethodInspection */
        return self::getInstance()->putFile($fname, $callback);
    }

    static function remove($id, $group)
    {
        return self::getInstance()->remove($id, $group);
    }
}
