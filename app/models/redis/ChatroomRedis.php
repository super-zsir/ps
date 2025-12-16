<?php

namespace Imee\Models\Redis;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisHash;

class ChatroomRedis extends BaseRedis
{
    const ONLINE_KEY = 'Xs.Room.Online';

    private static function getConnection()
    {
        static $connection;
        if ($connection) {
            return $connection;
        }
        $connection = new RedisBase(RedisBase::REDIS_CACHE);
        return $connection;
    }

    /**
     * 获取房间在线人数
     */
    public static function getOnlineCount()
    {
        return self::getConnection()->get(self::ONLINE_KEY);
    }
}