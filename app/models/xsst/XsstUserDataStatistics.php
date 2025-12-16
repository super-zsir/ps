<?php

namespace Imee\Models\Xsst;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Models\Xs\XsUserIdcard;
use Imee\Models\Xs\XsUserSafeMobile;
use Imee\Service\Helper;

class XsstUserDataStatistics extends BaseModel
{
    const STATICS_ONLINE_TIME = "admin.statics.online.time";
    const STATICS_CONSUME_USER_NUM = "admin.statics.consume.user.num";
    const STATICS_INCOME_USER_NUM = "admin.statics.income.user.num";
    const STATICS_ACTION_DAY_NUM = "admin.statics.action.day.num";

    public static function insertRows($data)
    {
        if (empty($data)) {
            return false;
        }

        foreach ($data as $v) {
            try {
                $rec = self::findFirst(array(
                    "uid=:uid:",
                    "bind" => array('uid' => $v['uid'])
                ));
                if ($rec) {
                    $needUpdate = 0;        //检查每一项数据是否相同，不同才更新，减少和数据库通讯次数
                    foreach ($v as $pk => $pv) {
                        if ($rec->{$pk} != $pv) {
                            $needUpdate = 1;
                        }
                        $rec->{$pk} = $pv;
                    }
                    if ($needUpdate) {
                        $rec->save();
                    }
                } else {
                    $rec = new self();
                    foreach ($v as $pk => $pv) {
                        $rec->{$pk} = $pv;
                    }
                    $rec->save();
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return true;
    }

    // 获取用户是否验证
    public static function checkUserIdValid($user_ids): array
    {
        if (empty($user_ids)) return [];
        if (is_string($user_ids)) $user_ids = explode(',', $user_ids);

        // 是否身份认证 -> xs_user_idcard:is_have(uid AND state in(3,4))
        $res = XsUserIdcard::find([
            'columns'    => 'uid',
            'conditions' => 'uid IN ({user_ids:array}) AND state IN ({status:array})',
            'bind'       => ['user_ids' => $user_ids, 'status' => [3, 4]]
        ])->toArray();
        $valid_user_ids = array_column($res, 'uid');
        $result = [];
        foreach ($user_ids as $user_id) {
            $result[$user_id] = in_array($user_id, $valid_user_ids) ? 1 : 0;
        }

        return $result;
    }

    /**
     * NOTES: 消费/收入人数（历史累计给多少人消费/收到过，包含送礼物/开礼盒/送守护/下单）
     * @param $user_ids
     * @param int $type 1 消费人数 2 收入人数
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public static function getConsumePersonCount($user_ids, $type = 1, $start_time = '', $end_time = '')
    {
        if (!$user_ids || !$start_time || !$end_time) return array();

        $op_type = ($type == 1) ? 'consume' : 'income';
        $from_to = ($type == 1) ? 'to' : 'from';

        // 更改实现方式 从redis中读取历史数据,增量的写进redis
        $result = array();
        $redis = new RedisBase(RedisBase::REDIS_CACHE);

        foreach ($user_ids as $k => $v) {
            if ($type == 1) {
                $key = self::STATICS_CONSUME_USER_NUM . $v;
            } else if ($type == 2) {
                $key = self::STATICS_INCOME_USER_NUM . $v;
            }

            $res = $redis->zcard($key);
            if (!$res) {
                $payChangeData = Helper::fetch("select * from xs_pay_change_new where uid = {$v} and op = '{$op_type}' and dateline >= {$start_time}
									and dateline < {$end_time} order by id asc", null, 'xsslavedb');
                if (!empty($payChangeData)) {
                    foreach ($payChangeData as $pk => $pv) {
                        $reason = unserialize($pv['reason']);
                        $redis->zadd($key, $pv['dateline'], $reason[$from_to]);

                    }
                }
            } else {

                $yesterdayTime = strtotime(date('Y-m-d', time() - 86400));
                $payChangeData = Helper::fetch("select * from xs_pay_change_new where uid = {$v} and op = '{$op_type}' and dateline >= {$yesterdayTime}
									and dateline < {$end_time} order by id asc", null, 'xsslavedb');
                if (!empty($payChangeData)) {
                    foreach ($payChangeData as $pk => $pv) {
                        $reason = unserialize($pv['reason']);
                        $redis->zadd($key, $pv['dateline'], $reason[$from_to]);

                    }
                }
            }

            // 将30天之外的数据都删除
            $redis->zremrangebyscore($key, 0, $start_time);
            $tmpResult = $redis->zrange($key, 0, -1);
            $result[$v] = count($tmpResult);

        }

        return $result;
    }

    public static function getYesterdayKeyAccount($user_ids)
    {
        if (empty($user_ids)) return [];
        if (is_string($user_ids)) $user_ids = explode(',', $user_ids);

        // 获取所有安全手机号
        $safe_infos = XsUserSafeMobile::find([
            'columns'    => 'uid,mobile',
            'conditions' => 'uid IN ({user_ids:array})',
            'bind'       => ['user_ids' => $user_ids]
        ])->toArray();
        $safe_mobiles = array_values(array_filter(array_unique(array_column($safe_infos, 'mobile'))));
        $safe_infos = array_column($safe_infos, 'mobile', 'uid');
        //获取历史上所有使用此安全手机号的用户
        $safe_key_user_infos = [];
        $safe_mobiles && $safe_key_user_infos = XsUserSafeMobile::find([
            'columns'    => 'uid,mobile',
            'conditions' => 'mobile IN ({mobiles:array})',
            'bind'       => ['mobiles' => $safe_mobiles]
        ])->toArray();
        $safe_key_user_infos
        && $safe_key_user_infos = self::array_group_by($safe_key_user_infos, 'mobile');

        $key_user_infos = [];
        foreach ($user_ids as $user_id) {
            if (empty($safe_infos[$user_id])) {
                $key_user_infos[$user_id] = [];
                continue;
            }

            //获取用户最近一次登陆的设备及安全手机号
            $history_mac_info = XsstMacUid::findFirst([
                'columns'    => 'mac',
                'conditions' => "uid=$user_id",
                'order'      => 'id DESC',
            ]);
            if (!$history_mac_info) {
                $key_user_infos[$user_id] = [];
                continue;
            }
            //统计获取昨日所有使用此设备登陆的用户
            $mac = $history_mac_info['mac'];
            $key_mac_user_infos = XsstMacUid::find([
                'columns'    => 'DISTINCT uid',
                'conditions' => "mac='$mac'",
            ])->toArray();
            $key_mac_user_infos = array_column($key_mac_user_infos, 'uid');

            //取交集即为同时使用此安全手机号和设备登陆的有效账号,去除自己
            $safe_key_user_uids = array_column($safe_key_user_infos[$safe_infos[$user_id]], 'uid');
            $key_mac_uids = array_intersect($key_mac_user_infos, $safe_key_user_uids);
            $key = array_search($user_id, $key_mac_uids);
            if ($key !== false) {
                unset($key_mac_uids[$key]);
                $key_mac_uids = array_values($key_mac_uids);
            }
            $key_user_infos[$user_id] = $key_mac_uids;
        }

        return $key_user_infos;
    }

    /**
     * @param $arr array 二维数组
     * @param $key string 要分组的key
     * @return array 分组后的新数组
     */
    private static function array_group_by($arr, $key)
    {
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        // 支持递归分组
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $params = array_merge([$value], array_slice($args, 2, func_num_args() - 2));
                $grouped[$key] = call_user_func_array([self::class, 'array_group_by'], $params);
            }
        }
        return $grouped;
    }
}