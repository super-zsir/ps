<?php

namespace Imee\Service\Operate\User;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserVip;
use Imee\Models\Xsst\BmsUserVipLog;
use Imee\Models\Xsst\BmsVipSendDetail;
use Imee\Service\Helper;
use Imee\Service\Operate\VipsendService;
use Imee\Service\Rpc\PsService;

class UserVipService
{
    public static function getUserVipList(int $uid): array
    {
        $levelMap = XsUserVip::$levelMap;
        if (!VipsendService::hasVip7Purview()) {
            foreach ($levelMap as $k => $v) {
                if ($v == 7) {
                    unset($levelMap[$k]);
                }
                if ($v == 8) {
                    unset($levelMap[$k]);
                }
            }
        }
        $userVip = XsUserVip::getAllUserVipByUid($uid);
        $list = [];
        foreach ($levelMap as $level) {
            if (!isset($userVip[$level])) {
                $list[] = [
                    'level'              => $level,
                    'vip_expire_time'    => '-',
                    'rebate_expire_time' => '-',
                    'status'             => 1
                ];
            } else {
                $l = $userVip[$level];
                $list[] = [
                    'level'              => $level,
                    'vip_expire_time'    => $l['vip_expire_time'] > 0 ? Helper::now($l['vip_expire_time']) : '-',
                    'rebate_expire_time' => $l['rebate_expire_time'] > 0 ? Helper::now($l['rebate_expire_time']) : '-',
                    'status'             => $l['vip_expire_time'] > time() ? 2 : 1
                ];
            }
        }
        return ['data' => $list, 'total' => count($list)];
    }

    public static function checkVip7(array $params): array
    {
        if ($params['type'] == 1) {
            // 检查VIP7条件
            $data = [
                [
                    'uid' => $params['uid'],
                    'vip_level' => $params['level'],
                ],
            ];
            [$success, $result] = VipsendService::checkVip7($data);
            if (!$success) {
                return ['is_info' => true, 'msg' => $result['msg']];
            }
        }
        return ['is_info' => false, 'msg' => ''];
    }

    public static function modify(array $params): array
    {
        $condition = [
            ['uid', '=', $params['uid']],
            ['level', '=', $params['level']]
        ];
        $vip = XsUserVip::findOneByWhere($condition);

        if ($params['type'] == 2) {

            if (in_array($params['level'], [7, 8])) {
                if (!VipsendService::hasVip7Purview()) {
                    return [false, sprintf('当前用户没有VIP%s权限，无法进行回收', $params['level'])];
                }
            }

            if ($params['day'] <= 0) {
                return [false, '回收VIP的天数应该为正整数，请检查后重试'];
            }

            $params['day'] = $params['day'] * -1;

            $expireTime = $vip['vip_expire_time'] + $params['day'] * 86400;

            if (!$vip) {
                return [false, '当前用户的VIP等级不存在无法进行回收'];
            } else {
                if ($expireTime < $vip['rebate_expire_time']) {
                    return [false, '扣除后VIP到期时间不可早于返钻到期时间'];
                }
            }

        } else {
            $cannotSendList = VipsendService::checkIfSendable((int)$params['level'], [$params['uid']]);
            if (!empty($cannotSendList)) {
                // 批量获取用户昵称
                $cannotSendUids = array_column($cannotSendList, 'uid');
                $userProfiles = XsUserProfile::getUserProfileBatch($cannotSendUids);
                $userNameMap = [];
                foreach ($userProfiles as $profile) {
                    $userNameMap[$profile['uid']] = $profile['name'];
                }
                
                // 从第一条记录获取阈值，动态计算显示（不转换单位，0位小数-千分位）
                $firstRecord = $cannotSendList[0];
                $requiredChargeAmtFormatted = number_format($firstRecord['required_charge_amt'], 0);
                $requiredRecvGiftFormatted = number_format($firstRecord['required_recv_gift_value'], 0);
                
                $errorMessage = "以下uid不满足发放条件，本次上传均发放失败，\n请调整条件后上传：上个自然月 充值≥{$requiredChargeAmtFormatted} 钻石 或者 收礼 ≥{$requiredRecvGiftFormatted} 钻石。\n\n";
                $errorMessage .= sprintf("%-10s %-10s %-15s %-15s\n", "uid", "昵称", "收礼钻石数", "充值钻石数");
                
                foreach ($cannotSendList as $item) {
                    // 格式化数值显示（不转换单位，0位小数-千分位）
                    $recvGiftFormatted = number_format($item['recv_gift_value'], 0);
                    $chargeAmtFormatted = number_format($item['charge_amt'], 0);
                    
                    // 获取用户真实昵称
                    $userName = $userNameMap[$item['uid']] ?? '未知用户';
                    
                    $errorMessage .= sprintf("%-10s %-10s %-15s %-15s\n", 
                        $item['uid'], 
                        mb_substr($userName, 0, 8), // 限制昵称长度，避免格式错乱
                        $recvGiftFormatted, 
                        $chargeAmtFormatted
                    );
                }
                
                throw new ApiException(ApiException::MSG_ERROR, $errorMessage);
            }
            
            if (!in_array($params['day'], BmsVipSendDetail::$allowDays)) {
                return [false, '填写的下发天数不支持，请检查后重试'];
            }
        }
        list($res, $msg) = (new PsService())->setUserVipTime($params);
        if ($res) {
            $params['before_expire_time'] = $vip['vip_expire_time'] ?? 0;
            self::addLog($params);
            return [true, ''];
        }

        return [false, $msg];
    }

    private static function addLog(array $params)
    {
        $time = 86400 * $params['day'];
        if ($params['before_expire_time'] > 0) {
            $params['after_expire_time'] = max($params['before_expire_time'] + $time, time());
        } else {
            $params['after_expire_time'] = max(time() + $time, time());
        }
        $data = [
            'uid'                => (int)$params['uid'],
            'vip_level'          => (int)$params['level'],
            'type'               => (int)$params['type'],
            'change_day'         => abs($params['day']),
            'after_expire_time'  => (int)$params['after_expire_time'],
            'before_expire_time' => (int)$params['before_expire_time'],
            'reason'             => $params['reason'],
            'admin_uid'          => (int)$params['admin_uid'],
            'dateline'           => time()
        ];
        return BmsUserVipLog::add($data);
    }

    public static function getUserVipLogList(array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 100);
        $order = trim($params['sort'] ?? 'id') . ' ' . trim($params['dir'] ?? 'desc');

        $res = BmsUserVipLog::getListAndTotal([
            ['uid', '=', $params['uid']]
        ], '*', $order, $page, $limit);
        if ($res['total'] == 0) {
            return [];
        }
        foreach ($res['data'] as &$v) {
            $v['after_expire_time'] = Helper::now($v['after_expire_time']);
            $v['dateline'] = Helper::now($v['dateline']);
            $v['before_expire_time'] = $v['before_expire_time'] > 0 ? Helper::now($v['before_expire_time']) : '-';
            $v['admin'] = Helper::getAdminName($v['admin_uid']);
        }
        return $res;
    }
}