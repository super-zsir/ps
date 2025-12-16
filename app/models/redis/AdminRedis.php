<?php

namespace Imee\Models\Redis;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;

class AdminRedis extends BaseRedis
{
    const AUDIT_QUARTILE = "auditQuartile";
    const NEW_QUA = "newQuartile";

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
     * 获取p90数据
     * @param array $keys
     * @return mixed
     */
    public static function getQuartile(array $keys)
    {
        return self::getConnection()->hmGet(self::AUDIT_QUARTILE, $keys);
    }

    /**
     * 设置p90数据
     * @param string $redis_key
     * @param array $value
     * @return mixed
     */
    public static function setQuartile(string $redis_key, array $value)
    {
        return self::getConnection()->hSet(self::AUDIT_QUARTILE, $redis_key, json_encode($value));
    }

    /**
     * 删除缓存
     * @param string $redis_key
     * @return mixed
     */
    public static function delQuartile(string $redis_key)
    {
        return self::getConnection()->hDel(self::AUDIT_QUARTILE, $redis_key);
    }

    /**
     * 缓存p90数据
     * @param $key
     * @param $type
     * @param $value
     * @return void
     */
    public static function setQua($key, $type, $value)
    {
        $redisKey = self::key(self::NEW_QUA, $key);
        self::getConnection()->hSet($redisKey, $type, $value);
        self::getConnection()->expire($redisKey, 7 * 86400);
    }

    /**
     * 获取缓存p90数据
     * @param $key
     * @param $type
     * @return mixed
     */
    public static function getQua($key, $type)
    {
        $redisKey = self::key(self::NEW_QUA, $key);
        return self::getConnection()->hGet($redisKey, $type);
    }
}