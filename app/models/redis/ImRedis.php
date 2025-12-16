<?php


namespace Imee\Models\Redis;


use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;

class ImRedis extends BaseRedis
{
    const PUSH_CONDITIONS_KEY = 'im:ul:{conditions}:{date}';

    private static function getConnection()
    {
        static $connection;
        if ($connection) {
            return $connection;
        }
        $connection = new RedisSimple(RedisBase::REDIS_ADMIN);
        return $connection;
    }

    /**
     * 添加条件对应用户列表
     *
     * @param string $key
     * @param array $uidList
     * @return bool
     */
    public static function addConditionsUserList(string $key, array $uidList = []): bool
    {
        if (empty($uidList)) {
            return false;
        }
        foreach (array_chunk($uidList, 1000) as $chunk) {
            self::getConnection()->rPush($key, json_encode($chunk));
        }
        return true;
    }

    /**
     * 判断key是否存在
     *
     * @param string $key
     * @return bool
     */
    public static function isConditions(string $key): bool
    {
        return self::getConnection()->has($key);
    }

    /**
     * 用迭代器批量获取uid
     *
     * @param string $key
     * @param int $limit
     * @return \Generator
     */
    public static function getConditionsList(string $key, int $limit = 5): \Generator
    {
        $start = 0;

        while (true) {
            $end = $start + $limit - 1;
            $chunk = self::getConnection()->lRange($key, $start, $end);

            if (empty($chunk)) {
                break; // 没有数据了
            }

            yield $chunk;

            $start += $limit;
        }
    }

    /**
     * 设置key的缓存时间
     * @param string $key
     * @return false
     */
    public static function setExpire(string $key): bool
    {
        // 只缓存到今天的23:59:59
        $expire = strtotime(date('Y-m-d 23:59:59')) - time();
        return self::getConnection()->expire($key, $expire);
    }

    /**
     * 设置条件缓存的key
     *
     * @param array $conditions
     * @return string
     */
    public static function setConditionsKey(array $conditions): string
    {
        // 针对数组做下排序
        asort($conditions);
        $conditionHash = substr(md5(json_encode($conditions)), 0, 8);
        $date = date('Ymd');

        return str_replace(
            ['{conditions}', '{date}'],
            [$conditionHash, $date],
            self::PUSH_CONDITIONS_KEY);
    }


}