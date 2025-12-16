<?php

namespace Imee\Comp\Common\Redis;

class RedisBase
{
    //存储缓存数据的数据
    const REDIS_CACHE = 'redis';
    //日志的消息队列
    const REDIS_LOG = 'redis';
    //admin单独使用
    const REDIS_ADMIN = 'redis_admin';
    //h5
    const REDIS_H5 = 'redis_h5';
    //room
    const REDIS_ROOM = 'redis_room';
    //mate
    const REDIS_MATE = 'redis_mate';
    //user
    const REDIS_USER = 'redis_user';
    //data
    const REDIS_DATA = 'redis_data';
    //大小号
    const REDIS_BIG_SMALL_ACCOUNT = 'redis_big_small_account';

    /** @var \PhpiRedis $_redis */
    protected $_redis;
    protected $_hasConnected = false;

    protected $_connKey;

    protected $_exception = false;

    public function __construct($key = self::REDIS_CACHE)
    {
        $this->_connKey = $key;
    }

    public function setConnectError($exception = false)
    {
        $this->_exception = $exception;
    }

    protected function connect()
    {
        if (!$this->_hasConnected) {
            $this->_redis = PhpRedis::getInstance($this->_connKey, false);
            $this->_hasConnected = true;
        }
        return $this->_redis !== null ? true : false;
    }

    public function has($key)
    {
        if (!$this->connect()) return false;
        return $this->_redis->exists($key);
    }

    public function expire($key, $second)
    {
        if (!$this->connect()) return false;
        return $this->_redis->expire($key, $second);
    }

    public function delete($key)
    {
        if (!$this->connect()) return false;
        return $this->_redis->del($key);
    }

    public function keys($pattern)
    {
        if (!$this->connect()) return false;
        return $this->_redis->keys($pattern);
    }

    public function close($force = false)
    {
        if ($this->_redis && $force) $this->_redis->close();
    }

    public function multi()
    {
        if (!$this->connect()) return false;
        return $this->_redis->multi();
    }

    public function exec()
    {
        if (!$this->connect()) return false;
        return $this->_redis->exec();
    }

    public function scan(&$cursor)
    {
        if (!$this->connect()) return false;
        return $this->_redis->scan($cursor);
    }

    public function __call($name, $args)
    {
        if (!$this->connect()) return false;
        return call_user_func_array(array($this->_redis, $name), $args);
    }

    //igbinary_serialize
    protected function serialize($val)
    {
        return serialize($val);
    }

    //igbinary_unserialize
    protected function unserialize($val)
    {
        return unserialize($val);
    }

    public function flushDB()
    {
        if (!IS_CLI || !$this->connect()) return false;
        return $this->_redis->flushDB();
    }

    public function ttl($key)
    {
        if (!$this->connect()) return false;
        return $this->_redis->ttl($key);
    }

    public function eval($lua, $data, $num)
    {
        if (!$this->connect()) return false;
        return $this->_redis->eval($lua, $data, $num);
    }

    public function pingRedis()
    {
        if ($this->connect() && $this->_redis->ping() != '+PONG') {
            $this->close(true);
            $this->_hasConnected = false;
            $this->connect();
        }
    }
}