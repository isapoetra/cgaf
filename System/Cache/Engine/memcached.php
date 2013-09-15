<?php
/**
 * User: Iwan Sapoetra
 * Date: 9/15/13
 * Time: 10:03 AM
 */

namespace System\Cache\Engine;
use System\Applications\IApplication;

class MemCached implements ICacheEngine
{
    private $_timeout;
    private $_appOwner;
    /**
     * @var \Memcached
     */
    private $_mcached;

    function __construct(IApplication $appOwner, $timeout = null)
    {
        $this->_appOwner = $appOwner;

        $server = $appOwner->getConfigs('cache.memcached.servers', array(
            array(
                'host' => 'localhost',
                'port' => 11211
            )
        ));
        $this->_mcached = new \Memcached($appOwner->getACL());
        $this->_mcached->addServers($server);
    }

    function remove($id, $group, $force = true, $ext = null)
    {
        return $this->_mcached->delete($id . $group);
    }

    function get($id, $prefix, $suffix = null, $timeout = NULL, callable $cb = null)
    {
        return $this->_mcached->getByKey($this->_appOwner->getAppId(), $id . $prefix . $suffix);
    }

    function clear()
    {
        $this->_mcached->flush();
    }

    public function putString($s, $id, $ext = null)
    {
        return $this->put($id, $s, '__default');
    }

    public function getId($o)
    {
        return md5($o);
    }

    public function put($id, $o, $group, $add = false, $ext = null)
    {
        $this->_mcached->setByKey($this->_appOwner->getAppId(), $id . $group, $o);
        return $this->_mcached->getResultCode() === \Memcached::RES_SUCCESS ? true : false;
    }

    public function setCacheTimeOut($int)
    {
        $this->_timeout = $int;
    }
}