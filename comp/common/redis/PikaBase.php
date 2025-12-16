<?php

namespace Imee\Comp\Common\Redis;

/**
 * pika
 * @author djw
 * @link https://github.com/OpenAtomFoundation/pika/blob/master/docs/ops/API.md
 */
class PikaBase extends RedisBase
{
    // Admin专用
    const PIKA_ADMIN = 'pika_admin';

    public function __construct($key = self::PIKA_ADMIN)
    {
        $this->_connKey = $key;
    }

    public function getData($key, $default = '')
    {
        return $this->get($key);
    }

    public function hgetData($key, $field, $default = '')
    {
        return $this->hget($key, $field);
    }

    public function hgetallData($key)
    {
        return $this->hgetall($key);
    }
}