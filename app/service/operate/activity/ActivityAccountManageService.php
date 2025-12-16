<?php

namespace Imee\Service\Operate\Activity;

use Imee\Models\Xs\XsActSendDiamondCheck;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class ActivityAccountManageService
{
    public function getList(): array
    {
        $bigAreaList = XsBigarea::getAreaList();

        $list = [];
        $uids = ENV == 'dev' ? XsBigarea::$accountUidDevMap : XsBigarea::$accountUidMap;
        $moneyList = XsUserMoney::getBatchCommon(array_values($uids), ['uid', 'money', 'money_b']);
        foreach ($bigAreaList as $bigArea) {
            if (isset($uids[$bigArea['id']])) {
                $uid = $uids[$bigArea['id']];
                $balance = 0;
                if (isset($moneyList[$uid])) {
                    $balance = $moneyList[$uid]['money'] + $moneyList[$uid]['money_b'];
                }
                $tmp = [
                    'id'      => $bigArea['id'],
                    'name'    => $bigArea['cn_name'],
                    'uid'     => $uid,
                    'balance' => $balance,
                ];
                $list[] = $tmp;
            }
        }

        return $list;
    }

    public function getStatus()
    {
        return StatusService::formatMap(XsActSendDiamondCheck::$statusMap, 'label,value');
    }

    public function getActType()
    {
        return StatusService::formatMap(XsActSendDiamondCheck::$actTypeMap, 'label,value');
    }

    public function getSendList(array $params): array
    {
        $list = XsActSendDiamondCheck::getListAndTotal($this->getSendConditions($params), '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $userBigAreaList = XsUserBigarea::getUserBigAreaBatch(Helper::arrayFilter($list['data'], 'uid'), ['uid', 'bigarea_id'], 'bigarea_id');
        foreach ($list['data'] as &$item) {
            if ($item['status'] == XsActSendDiamondCheck::ERROR_STATUS) {
                $item['status'] = "<font color='red'>" . (XsActSendDiamondCheck::$statusMap[$item['status']] ?? '') ."</font>";
            } else {
                $item['status'] = XsActSendDiamondCheck::$statusMap[$item['status']] ?? '';
            }
            $item['user_bigarea_id'] = $userBigAreaList[$item['uid']] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    private function getSendConditions(array $params): array
    {
        $conditions = [];
        foreach (['bigarea_id', 'uid', 'act_type', 'status', 'act_id'] as $filed) {
            if (isset($params[$filed])) {
                $conditions[] = [$filed, '=', $params[$filed]];
            }
        }

        if (isset($params['start_time']) && !empty($params['start_time'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['start_time'])];
        }

        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $conditions[] = ['dateline', '<', strtotime($params['end_time']) + 86400];
        }

        return $conditions;
    }
}