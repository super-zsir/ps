<?php

namespace Imee\Comp\Common\Language\Models\Redis;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;

class LanguageRedis extends RedisBase
{
    private static function getConnection()
    {
        static $connection;
        if ($connection) {
            return $connection;
        }
        $connection = new RedisSimple(RedisBase::REDIS_CACHE);
        return $connection;
    }

    /**
     * 获取模块对应翻译
     */
    public static function get($mid)
    {
        return self::getConnection()->get(self::getKey($mid));
    }

    /**
     * 设置模块对应翻译
     */
    public static function set($mid, $data)
    {
        return self::getConnection()->set(self::getKey($mid), $data);
    }

    private static function getKey($mid): string
    {
        // 如果mid 为0 时需要加一下系统id区分
        $systemId = $mid == 0 ? (':system_id:' . SYSTEM_ID) : '';
        return 'tle:lang:flag:' . SYSTEM_FLAG . 'mid:' . $mid . $systemId;
    }
}