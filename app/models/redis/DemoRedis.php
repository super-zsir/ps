<?php

/**
 * 这是一个demo
 * 所有调用redis的业务copy这个去实现
 */

namespace Imee\Models\Redis;

use Imee\Comp\Common\Redis\RedisSimple;

class DemoRedis extends BaseRedis
{
    const DEMO_KEY = 'demo:test';
    const DEMO_KEY_TTL = 600;

    private static function getConnection()
    {
        static $connection;
        if ($connection) {
            return $connection;
        }
        $connection = new RedisSimple();
        return $connection;
    }

    /**
     * 这是一个demo get
     */
    public static function getData($uid)
    {
        $redisKey = self::key(self::DEMO_KEY, $uid);
        return self::getConnection()->get($redisKey);
    }

    /**
     * 这是一个demo set
     */
    public static function setData($uid, $val): bool
    {
        $redisKey = self::key(self::DEMO_KEY, $uid);
        return self::getConnection()->set($redisKey, $val, self::DEMO_KEY_TTL);
    }

    /**
     * 批量处理
     * @param $uidArr
     * @param $data
     * @return void
     * @throws \Exception
     */
    public static function batchData($uidArr, $data)
    {
        $redis = self::getConnection();
        $pipe = $redis->multi();
        foreach ($uidArr as $uid) {
            $redisKey = self::key(self::DEMO_KEY, $uid);
            $pipe->set($redisKey, $data, self::DEMO_KEY_TTL);
        }
        return $pipe->exec();
    }

    /**
     * 乐观锁
     * @param $uid
     * @param $data
     * @return void
     * @throws \Exception
     */
    public static function optimisticLock($uid, $data)
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::DEMO_KEY, $uid);
        $redis->watch($redisKey);
        $pipe = $redis->multi();
        $pipe->set($redisKey, $data, self::DEMO_KEY_TTL);
        return $pipe->exec();
    }

    /**
     * lua脚本批量操作
     * 更高效，原子操作
     */
    public static function batchSet()
    {
        $redis = self::getConnection();
        $config = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $lua = <<<SCRIPT
local config = cjson.decode(KEYS[1])
for k, v in pairs(config) do
    redis.pcall('set', k, v)
    redis.pcall('expire', k, 60)
end
return 1
SCRIPT;
        return $redis->eval($lua, [json_encode($config)], 1);
    }
}