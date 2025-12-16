<?php

namespace Imee\Models\Xsst;

use Imee\Service\Helper;

class XsstUserChatmessage extends BaseModel
{
    /**
     * 有效聊天用户数：当天用户互动聊天次数1次以上
     */
    public static function calculValidChatUser($from_uids, $start_time = '', $end_time = '', $db = "")
    {

        if (empty($from_uids)) return [];

        empty($start_time) && $start_time = strtotime("-1 days");
        empty($end_time) && $end_time = strtotime(date('Y-m-d', $start_time) . " 23:59:59");

        //获取用户聊天的所有对象uid
        $from_uid_arr = array_chunk($from_uids, 200);
        $validUserArr = [];
        foreach ($from_uid_arr as $from_uid_sub_arr) {
            //发起聊天的用户组
            $fromUids = self::find([
                'columns'    => 'uid,touid',
                'conditions' => "uid IN ({uids:array}) AND date BETWEEN :start_time: AND :end_time:",
                'bind'       => [
                    'uids'       => $from_uid_sub_arr,
                    'start_time' => $start_time,
                    'end_time'   => $end_time
                ],
                'group'      => 'uid,touid',
            ])->toArray();

            // 接收到聊天的用户组
            $replyUids = array_values(array_unique(array_column($fromUids, 'touid')));
            $beginUids = array_values(array_unique(array_column($fromUids, 'uid')));
            if ($replyUids && $beginUids) {
                $validUserResult = self::find([
                    'columns'    => 'count(uid) AS valid_user_count,touid',
                    'conditions' => "uid IN ({uids:array}) AND touid IN ({touids:array}) AND date BETWEEN :start_time: AND :end_time:",
                    'bind'       => [
                        'uids'       => $replyUids,
                        'touids'     => $beginUids,
                        'start_time' => $start_time,
                        'end_time'   => $end_time
                    ],
                    'group'      => 'touid',
                ])->toArray();

                //获取所有有效的用户
                $validUserArr = $validUserArr + array_column($validUserResult, 'valid_user_count', 'touid');
            }
        }
        return $validUserArr;
    }


    /**
     * @desc 大神聊天/回复人数统计，按uid分组统计
     * @param array $uidArr 用户Id数组
     * @param int $timeStart 开始时间
     * @param int $timeEnd 结束时间
     * @param int $type 类型：1 回复; 2，聊天
     * @return array
     */
    public static function toChatManCount($uidArr, $timeStart = 0, $timeEnd = 0, $type = 2)
    {
        if (empty($uidArr)) return [];
        $ids = implode(',', $uidArr);
        $uidType = ($type == 1) ? 'touid' : 'uid';
        $distinctField = ($type == 1) ? 'uid' : 'touid';
        $allCountSql = "select `{$uidType}`, count(DISTINCT({$distinctField})) as num from xsst_user_chatmessage where `{$uidType}` in ({$ids})
		and `date`>={$timeStart} and `date`<{$timeEnd} group by `{$uidType}`";
        $dataTmp = Helper::fetch($allCountSql, null, 'xsstdb');
        $result = array_column($dataTmp, 'num', "{$uidType}");
        foreach ($uidArr as $val) {
            if (empty($result[$val])) {
                $result[$val] = 0;
            }
        }
        return $result;
    }

    /**
     * @desc 大神聊天/回复数统计，按uid分组统计
     * @param array $uidArr 用户Id数组
     * @param int $timeStart 开始时间
     * @param int $timeEnd 结束时间
     * @param int $type 类型：1，回复; 2，聊天
     * @return array
     */
    public static function toChatCount($uidArr, $timeStart = 0, $timeEnd = 0, $type = 2)
    {
        if (empty($uidArr)) return [];

        $ids = implode(',', $uidArr);
        $uidType = ($type == 1) ? 'touid' : 'uid';
        $distinctField = ($type == 1) ? 'uid' : 'touid';
        $allCountSql = "select `{$uidType}`, count({$distinctField}) as num from xsst_user_chatmessage where `{$uidType}` in ({$ids})
		and `date`>={$timeStart} and `date`<{$timeEnd} group by `{$uidType}`";
        $dataTmp = Helper::fetch($allCountSql, null, 'xsstdb');
        $result = array_column($dataTmp, 'num', "{$uidType}");
        foreach ($uidArr as $val) {
            if (empty($result[$val])) {
                $result[$val] = 0;
            }
        }
        return $result;
    }
}
