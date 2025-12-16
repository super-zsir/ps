<?php


namespace Imee\Comp\Common\Export\Models\Redis;


use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;

class ExportTaskRedis extends BaseRedis
{
    const REDIS_EXPORT_LOCK_KEY = 'admin_export';
    const REDIS_EXPORT_TASK_LOCK_KEY = 'admin_export_task';
    const REDIS_EXPORT_TASK_RETRY_KEY = 'admin_export_task_retry';

    private static function getConnection()
    {
        static $connection;
        if ($connection) {
            return $connection;
        }
        $connection = new RedisSimple(RedisBase::REDIS_ADMIN);
        return $connection;
    }

    public static function checkFirstExportFlag($opUid, $fileName, $filterParams): bool
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::REDIS_EXPORT_LOCK_KEY, SYSTEM_FLAG, md5($opUid . $fileName . json_encode($filterParams)));
        if ($redis->incr($redisKey) == 1) {
            $redis->expire($redisKey, 300);
            return true;
        }
        return false;
    }

    public static function deleteFirstExportFlag($opUid, $fileName, $filterParams): bool
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::REDIS_EXPORT_LOCK_KEY, SYSTEM_FLAG, md5($opUid . $fileName . json_encode($filterParams)));
        return $redis->delete($redisKey);
    }

    public static function checkFirstTaskFlag($id): bool
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::REDIS_EXPORT_TASK_LOCK_KEY, $id);
        if ($redis->incr($redisKey) == 1) {
            $redis->expire($redisKey, 300);
            return true;
        }
        return false;
    }

    public static function deleteFirstTaskFlag($id): bool
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::REDIS_EXPORT_TASK_LOCK_KEY, $id);
        return $redis->delete($redisKey);
    }

    public static function checkRetryTaskFlag($id): int
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::REDIS_EXPORT_TASK_RETRY_KEY, $id);
        $val = $redis->incr($redisKey);
        if ($val == 1) {
            $redis->expire($redisKey, 300);
            return 1;
        }
        return (int)$val;
    }

    public static function deleteRetryTaskFlag($id): bool
    {
        $redis = self::getConnection();
        $redisKey = self::key(self::REDIS_EXPORT_TASK_RETRY_KEY, $id);
        return $redis->delete($redisKey);
    }
}