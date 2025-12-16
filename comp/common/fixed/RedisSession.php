<?php

namespace Imee\Comp\Common\Fixed;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;
use Phalcon\Session\Adapter;
use Phalcon\Session\AdapterInterface;

class RedisSession extends Adapter implements AdapterInterface
{
    protected $_redis = null;
    protected $_lifetime = 7200;

    public function __construct(array $options = null)
    {
        if ($options == null) $options = array();
        if (isset($options['lifetime'])) $this->_lifetime = $options['lifetime'];
        $this->_redis = new RedisSimple(RedisBase::REDIS_ADMIN);

        session_set_save_handler(
            array($this, "open"),
            array($this, "close"),
            array($this, "read"),
            array($this, "write"),
            array($this, "destroy"),
            array($this, "gc")
        );

        parent::__construct($options);
    }

    public function open()
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($sessionId)
    {
        $r = $this->_redis->get($sessionId);
        if ($r === false) return "";
        return $r;
    }

    public function write($sessionId, $data)
    {
        return $this->_redis->set($sessionId, $data, $this->_lifetime);
    }

    public function destroy($sessionId = null)
    {
        if ($sessionId === null) {
            $sessionId = $this->getId();
        }
        return $this->_redis->delete($sessionId);
    }

    public function gc()
    {
        return true;
    }
}