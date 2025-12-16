<?php

namespace Imee\Service\Operate\User;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsPay;
use Imee\Models\Xs\XsPayAccount;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserIdcard;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSafeMobile;
use Imee\Models\Xsst\XsstMacUid;

class UserService
{
    /**
     * @desc 根据身份证获取性别（兼容15和18位）,海外身份证直接返回-1
     * @param string $card 身份证
     * @param int $type 身份证类型：0大陆；1台湾
     * @return int 1表示男；0表示女；-1表示未知
     */
    public function getSexByIdCard($card, $type = 0)
    {
        $sex = -1;
        if ($type == 0) {
            $cardLen = strlen($card);
            if (!in_array($cardLen, [15, 18])) {
                return $sex;
            }
            $sex = substr($card, (strlen($card) == 15 ? -1 : -2), 1) % 2 ? 1 : 0;
        } elseif ($type == 1) {
            if (strlen($card) != 10) {
                return $sex;
            }
            $sex = substr($card, 1, 1) % 2 ? 1 : 0;
        }
        return $sex;
    }

    public function idcardIndex($params)
    {
        if (empty($params['uid'])) {
            return [];
        }
        $uid = trim($params['uid']);
        $uid = str_replace("，", ",", $uid);
        $uidArr = explode(",", $uid);
        $uidArr = array_filter($uidArr);

        $condition = [];
        $condition[] = ['uid', 'in', $uidArr];
        $data = XsUserIdcard::getListByWhere($condition);

        $uids = array_column($data, "uid");
        $uidsData = XsUserProfile::findByIds($uids);
        $userMap = array_column($uidsData, null, "uid");
        $dataMap = array_column($data, null, "uid");
        $result = [];
        foreach ($uidArr as $k => $v) {
            $tmp = [];
            $tmpArr = [];
            $v = intval($v);    //必须转换类型，不然出错
            $tmpArr['id'] = $k + 1;
            $tmpArr['uid'] = $v;
            if (!empty($dataMap[$v])) {
                $tmpArr['state'] = $dataMap[$v]['state'];
                $tmpArr['num'] = $dataMap[$v]['cardnum'];
                $tmpArr['name'] = $dataMap[$v]['cardname'];
                $tmpArr['idarea'] = $dataMap[$v]['idarea'];
                $tmpArr['is_relate'] = '否';
            } else {
                $safeMobile = XsUserSafeMobile::findFirstValue($v);
                if ($safeMobile) {
                    //获取appId的逻辑改到if里面来，可以减少不必要的sql连接次数
                    $uprofile = XsUserProfile::findFirstValue($v);
                    if ($uprofile) {
                        $appId = $uprofile->app_id;
                        $uids = XsUserSafeMobile::findAllUids($appId, $safeMobile->mobile);
                        if (!empty($uids)) {
                            $tmp = XsUserIdcard::findFirst(array(
                                "uid in ({ids:array}) and state > 2",
                                "bind" => array("ids" => $uids)
                            ));
                        }
                    }
                }
                $tmpArr['is_relate'] = '否';
                if ($tmp) {
                    $tmpArr['state'] = $tmp->state;
                    $tmpArr['num'] = $tmp->cardnum;
                    $tmpArr['name'] = $tmp->cardname;
                    $tmpArr['idarea'] = $tmp->idarea;
                    $tmpArr['is_relate'] = '是';
                }
            }
            $tmpArr['nickname'] = $userMap[$v]['name'] ?? '-';
            $tmpArr['usersex'] = $userMap[$v]['sex'] ?? 0;
            $tmpArr['sex'] = isset($tmpArr['num']) ? $this->getSexByIdCard($tmpArr['num'], $tmpArr['idarea']) : -1;
            $tmpArr['num'] = isset($tmpArr['num']) ? substr($tmpArr['num'], -6) : '-';
            $tmpArr['name'] = $tmpArr['name'] ?? '-';
            $result[] = $tmpArr;
        }

        return $result;
    }

    /**
     * 从老后台关联账号迁移过来
     * @param $params
     * @return array
     */
    public function relationAccount($params): array
    {
        if (empty($params['uid'])) {
            return [];
        }
        $reason = $params['reason'] ?? [];
        $params['uid'] = str_replace('，', ',', $params['uid']);
        $uidArr = explode(',', $params['uid']);

        $uidArr = array_filter($uidArr, function ($uid) {
            return is_numeric($uid) && $uid > 0;
        });
        if (!$uidArr) {
            return [];
        }
        $uidArr = array_values(array_unique($uidArr));

        $users = XsUserProfile::getUserProfileBatch($uidArr, ['uid']);
        if (!$users) {
            return [];
        }
        $uidArr = array_keys($users);
        $data = [];

        if (empty($reason[0]) || in_array(1, $reason)) {
            $result = XsUserSafeMobile::getListByWhere([['uid', 'in', $uidArr]], 'uid,mobile');
            if ($result) {
                $queryResult = XsUserSafeMobile::getListByWhere([['mobile', 'in', array_column($result, 'mobile')]], 'uid,mobile');

                $uids = array_values(array_diff(array_column($queryResult, 'uid'), $uidArr));
                if ($uids) {
                    $uids = XsUserProfile::findByIds($uids, 'uid');
                    $uids = array_column($uids, 'uid');
                }
                $uids = array_merge($uidArr, $uids);

                foreach ($result as $item) {
                    $uid = $item['uid'];
                    $mobile = $item['mobile'];

                    foreach ($queryResult as $rec) {
                        if (
                            $rec['mobile'] == $mobile &&
                            $rec['uid'] != $uid &&
                            in_array($rec['uid'], $uids)
                        ) {
                            if (!isset($data[$uid])) {
                                $data[$uid] = [];
                            }
                            $data[$uid][$rec['uid']] = [1];
                        }
                    }
                }
            }
        }

        if (empty($reason[0]) || in_array(2, $reason)) {
            $result = XsPayAccount::getListByWhere([['uid', 'in', $uidArr], ['platform', 'not in', ['adyen', 'checkout', 'stripe']]], 'uid,platform,buyer_id');
            if ($result) {
                $queryResult = XsPayAccount::getListByWhere([
                    ['platform', 'in', array_column($result, 'platform')],
                    ['buyer_id', 'in', array_column($result, 'buyer_id')],
                ], 'uid,platform,buyer_id');

                $uids = array_values(array_diff(array_column($queryResult, 'uid'), $uidArr));
                if ($uids) {
                    $uids = XsUserProfile::findByIds($uids, 'uid');
                    $uids = array_column($uids, 'uid');
                }
                $uids = array_merge($uidArr, $uids);

                foreach ($result as $item) {
                    $uid = $item['uid'];
                    $platform = $item['platform'];
                    $buyerId = $item['buyer_id'];

                    foreach ($queryResult as $rec) {
                        if (
                            $rec['platform'] == $platform &&
                            $rec['buyer_id'] == $buyerId &&
                            $rec['uid'] != $uid &&
                            in_array($rec['uid'], $uids)
                        ) {
                            if (!isset($data[$uid])) {
                                $data[$uid][$rec['uid']] = [2];
                            } else {
                                if (isset($data[$uid][$rec['uid']])) {
                                    $data[$uid][$rec['uid']][] = 2;
                                } else {
                                    $data[$uid][$rec['uid']] = [2];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($reason[0]) || array_intersect(array(3, 4, 5, 6), $reason)) {
            //根据设备号获取关联账号
            //查出UID的Mac
            $result = XsstMacUid::userMacSorted($uidArr);
            if ($result) {
                $firstMac = array_map('reset', $result);
                $allMacs = array_column($firstMac, 'mac');

                $allMacs = [];
                foreach ($result as $userMacs) {
                    $allMacs = array_merge($allMacs, array_column($userMacs, 'mac'));
                }
                $allMacs = array_values(array_unique($allMacs));

                $queryResult = XsstMacUid::getListByWhere([['mac', 'in', $allMacs]], 'uid,mac');

                $uids = array_values(array_diff(array_column($queryResult, 'uid'), $uidArr));
                if ($uids) {
                    $uids = XsUserProfile::findByIds($uids, 'uid');
                    $uids = array_column($uids, 'uid');
                }
                $uids = array_merge($uidArr, $uids);

                $uidStr = implode(',', array_column($queryResult, 'uid'));
                $sql = "SELECT t1.uid, t1.mac FROM xsst_mac_uid t1
                        INNER JOIN (
                            SELECT uid, MIN(dateline) as min_dateline
                            FROM xsst_mac_uid
                            WHERE uid IN ({$uidStr})
                            GROUP BY uid
                        ) t2 ON t1.uid = t2.uid AND t1.dateline = t2.min_dateline";
                $userFirstMacs = XsstMacUid::fetchAllBySql($sql, null, XsstMacUid::SCHEMA_READ);
                $userFirstMacs = array_column($userFirstMacs, 'mac', 'uid');

                foreach ($result as $uid => $macs) {
                    $firstMac = array_shift($macs);

                    foreach ($queryResult as $rec) {
                        if ($firstMac['mac'] == $rec['mac'] && $uid != $rec['uid'] && in_array($rec['uid'], $uids)) {

                            if ($userFirstMacs[$rec['uid']] == $rec['mac']) {
                                if (!empty($reason[0]) && !in_array(3, $reason)) {
                                    continue;
                                }
                                $tempReason = 3;
                            } else {
                                if (!empty($reason[0]) && !in_array(4, $reason)) {
                                    continue;
                                }
                                //注册-使用
                                $tempReason = 4;
                            }
                            if (isset($data[$uid])) {
                                if (isset($data[$uid][$rec['uid']])) {
                                    $data[$uid][$rec['uid']][] = $tempReason;
                                } else {
                                    $data[$uid][$rec['uid']] = [$tempReason];
                                }
                            } else {
                                $data[$uid][$rec['uid']] = [$tempReason];
                            }
                        }
                    }

                    if ($macs) {
                        foreach ($queryResult as $rec) {
                            foreach ($macs as $mac) {
                                if ($mac['mac'] == $rec['mac'] && $uid != $rec['uid'] && in_array($rec['uid'], $uids)) {
                                    if ($userFirstMacs[$rec['uid']] == $rec['mac']) {
                                        if (!empty($reason[0]) && !in_array(5, $reason)) {
                                            continue;
                                        }
                                        //使用-注册
                                        $tempReason = 5;
                                    } else {
                                        if (!empty($reason[0]) && !in_array(6, $reason)) {
                                            continue;
                                        }
                                        //使用-使用
                                        $tempReason = 6;
                                    }

                                    if (isset($data[$uid])) {
                                        if (isset($data[$uid][$rec['uid']])) {
                                            $data[$uid][$rec['uid']][] = $tempReason;
                                        } else {
                                            $data[$uid][$rec['uid']] = [$tempReason];
                                        }
                                    } else {
                                        $data[$uid][$rec['uid']] = [$tempReason];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($data)) {
            return [];
        }
        $res = [];
        $uids = array_keys($data);
        $smallUids = [];
        foreach ($data as $list) {
            $uids = array_merge($uids, array_keys($list));
            $smallUids = array_merge($smallUids, array_keys($list));
        }
        $users = XsUserProfile::getUserProfileBatch($uids, ['uid', 'name', 'dateline', 'online_dateline']);
        $userBigareas = XsUserBigarea::getUserBigareas($uids);
        $bigareas = XsBigarea::getAllBigAreaCode();
        $reasonMap = XsUserProfile::$reasonArr;

        $firstPayTimes = XsPay::getFirstPayTimeByUids($smallUids);

        $priceLevel = UserPriceLevelService::getInstance()->getList($uids);

        foreach ($data as $uid => $list) {
            $userInfo = $users[$uid];
            $name = $userInfo['name'];
            $bigareaId = $userBigareas[$uid] ?? 0;
            $bigArea = $bigareas[$bigareaId] ?? '';
            foreach ($list as $smallUid => $_reasonArr) {
                $smallUserInfo = $users[$smallUid];
                $smallName = $smallUserInfo['name'];
                $bigareaId = $userBigareas[$smallUid] ?? 0;
                $smallBigArea = $bigareas[$bigareaId] ?? '';
                $payTime = $firstPayTimes[$smallUid] ?? 0;
                $_reasonArr = array_map(function ($val) use ($reasonMap) {
                    return $reasonMap[$val] ?? $val;
                }, $_reasonArr);
                $res[] = array(
                    'uid'                  => $uid,
                    'name'                 => $name,
                    'vip'                  => $priceLevel[$uid] ?? 0,
                    'big_area'             => XsBigarea::$_bigAreaMap[$bigArea] ?? $bigArea,
                    'small_uid'            => $smallUid,
                    'small_name'           => $smallName,
                    'small_vip'            => $priceLevel[$smallUid] ?? 0,
                    'small_big_area'       => XsBigarea::$_bigAreaMap[$smallBigArea] ?? $smallBigArea,
                    'small_register_time'  => $smallUserInfo['dateline'] > 0 ? date('Y-m-d H:i:d', $smallUserInfo['dateline']) : 0,
                    'small_online_time'    => $smallUserInfo['online_dateline'] > 0 ? date('Y-m-d', $smallUserInfo['online_dateline']) : '-',
                    'small_first_pay_time' => $payTime > 0 ? date('Y-m-d', $payTime) : '-',
                    'reason'               => implode(',', $_reasonArr),
                );
            }
        }

        return $res;
    }
}