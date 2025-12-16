<?php

namespace Imee\Comp\Common\Fixed;

class CacheXcache
{
    private $_serialize;
    private $_enabled;

    public function __construct($serialize = true)
    {
        $this->_serialize = $serialize;
        $this->_enabled = substr(php_sapi_name(), 0, 3) !== 'cli';
    }

    public function get($key)
    {
        if (!$this->has($key)) return false;
        return $this->unserialize(xcache_get($key));
    }

    public function mget($keys)
    {
        $data = array();
        foreach ($keys as $key) {
            $data[] = $this->get($key);
        }
        return $data;
    }

    public function set($key, $val, $time = 300)
    {
        if (!$this->_enabled) return false;
        if (is_null($time)) $time = 86400 * 365;
        return xcache_set($key, $this->serialize($val), $time);
    }

    public function has($key)
    {
        if (!$this->_enabled) return false;
        return xcache_isset($key);
    }

    public function delete($key)
    {
        if (!$this->_enabled) return false;
        return xcache_unset($key);
    }

    protected function serialize($val)
    {
        if (!$this->_serialize || is_numeric($val)) return $val;
        return serialize($val);
    }

    protected function unserialize($val)
    {
        if (!$this->_serialize || is_numeric($val)) return $val;
        return unserialize($val);
    }
}