<?php


namespace Imee\Models\Redis;


use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;

class CsmsRedis extends BaseRedis
{


    const CSMS_PUSH = 'partystar-csms:push';


    /*
     * csmspush
     */
    public static function csmsPush($data = [])
    {
        $redis = new RedisSimple(RedisBase::REDIS_ADMIN);
        return $redis->rPush(self::CSMS_PUSH, json_encode($data));
    }


    /**
     * lua 批量插入数据
     * @param array $data
     * @return false
     */
    public static function csmsLPush($key, $data = [])
    {
        if (!$data) return 0;
        $redis = new RedisSimple(RedisBase::REDIS_ADMIN);
        foreach ($data as $item){
            $redis->lPush($key, $item);
        }
        return true;

//        $lua = <<<SCRIPT
//local key = KEYS[1]
//local data = cjson.decode(ARGV[1])
//for k, v in pairs(data) do
//    redis.call('lpush', key, v)
//end
//return 1
//SCRIPT;
//        $res = $redis->eval($lua, [$key, json_encode($data)], 1);
//        return $res;
    }


    /**
     * csms push 获取队列任务
     * @param $key
     * @param int $number
     * @return false
     */
    public static function pushRange($key, $number = 50)
    {
        $redis = new RedisSimple(RedisBase::REDIS_ADMIN);
        $length = $redis->lLen($key);
        if(!$length){
            return [];
        }
        $lend = ($number >= $length) ? $length : $number;
        $list = $redis->lRange($key, 0, $lend - 1);
        $redis->lTrim($key, $lend, -1);
        return $list;

//        $lua = <<<SCRIPT
//local data = {}
//local lend = 0
//local keyname = KEYS[1]
//local number = tonumber(ARGV[1])
//local length = redis.call('llen', keyname)
//lend = (number >= length) and length or number
//data = redis.call('lrange', keyname, 0, lend - 1)
//redis.call('ltrim', keyname, lend , -1)
//return data
//SCRIPT;
//        $list = $redis->eval($lua, [$key, $number], 1);
//        return $list;
    }

    /**
     * 获取 redis list 长度
     * @param $key
     */
    public static function getLength($key)
    {
        $redis = new RedisSimple(RedisBase::REDIS_ADMIN);
        return $redis->lLen($key);
    }


}