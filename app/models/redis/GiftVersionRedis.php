<?php

namespace Imee\Models\Redis;

use Imee\Comp\Common\Redis\RedisBase;

class GiftVersionRedis extends BaseRedis
{
    const KEY = 'gift.version';

    private static function getConnection()
    {
        static $connection;
        if ($connection) {
            return $connection;
        }
        $connection = new RedisBase(RedisBase::REDIS_CACHE);
        return $connection;
    }

    public static function update(): array
    {
        $redis = self::getConnection();
        $rec = $redis->get(self::KEY);

        $data = [];
        $data['before'] = $rec;
        if ($rec) {
            $data['after'] = $redis->incr(self::KEY, 1);
        } else {
            $redis->set(self::KEY, 100);
            $data['after'] = 100;
        }
        return $data;
    }
}