<?php

namespace Imee\Service\Operate\Background\Custombackground;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class CustomBgcCardSendService
{
    public static $giveTypeMaps = [
        0 => '否',
        1 => '是',
    ];

    public function getList(array $params): array
    {
        $cond = $this->getConditions($params);
        $list = (new PsService())->customBgcCardLogList($cond);
        if (empty($list['data'])) {
            return [];
        }
        foreach ($list['data'] as &$v) {
            $v['can_transfer'] = intval($v['can_transfer'] ?? 0);
            $v['can_transfer'] = strval($v['can_transfer']);
            $v['valid_term'] = (int) ($v['valid_term'] / 86400);
            $v['big_area_id'] = (string)$v['bigarea_id'];
            $v['dateline'] = Helper::now($v['dateline']);
        }
        return $list;
    }

    public function send(array $params)
    {
        $uidArr = Helper::formatIdString($params['uid']);

        $data = [];
        foreach ($uidArr as $uid) {
            $data[] = [
                'uid'          => (int)$uid,
                'reason'       => $params['reason'],
                'num'          => (int)$params['num'],
                'valid_term'   => (int)$params['valid_term'],
                'can_transfer' => intval($params['can_transfer'] ?? 0),
                'card_type'    => intval($params['card_type'] ?? 0)
            ];
        }

        $this->sendBatch(['data' => $data], $params['admin_id']);
    }

    public function sendBatch(array $params, int $adminId)
    {
        $data = $this->packData($params['data'], $adminId);
        if (empty($data)) {
            throw new ApiException(ApiException::MSG_ERROR, '数据为空');
        }
        [$res, $msg] = (new PsService())->customBgcCardSend($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function packData($data, $adminId)
    {
        $admin = Helper::getAdminName($adminId);
        $time = time();
        $uids = array_column($data, 'uid');
        $userBigAreas = XsUserBigarea::getUserBigAreaBatch($uids);
        foreach ($data as &$item) {
            $item['can_transfer'] = intval($item['can_transfer'] ?? 0);
            $item['big_area_id'] = $userBigAreas[$item['uid']]['bigarea_id'] ?? 0;
            $item['valid_term'] = $item['valid_term'] * 86400;
            $item['operator'] = $admin;
            $item['dateline'] = $time;

            $cardType = $item['card_type'] ?? 0;
            if ($cardType === '静态') {
                $item['card_type'] = 0;
            } else if ($cardType === '动态') {
                $item['card_type'] = 1;
            }
            $item['card_type'] = intval($cardType);
        }
        return $data;
    }

    public function getConditions(array $params): array
    {
        $conditions = [
            'page_num' => $params['page'],
            'page_size' => $params['limit']
        ];
        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions['uid'] = $params['uid'];
        }
        if (isset($params['big_area_id']) && !empty($params['big_area_id'])) {
            $conditions['big_area_id'] = $params['big_area_id'];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions['start_time'] = strtotime($params['dateline_sdate']);
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions['end_time'] = strtotime($params['dateline_edate']) + 86400;
        }
        if (isset($params['card_type']) && is_numeric($params['card_type'])) {
            $conditions['card_type'] = intval($params['card_type']);
        } else {
            $conditions['card_type'] = -1;
        }

        return array_map('intval', $conditions);
    }

    public function getCardTypeMap()
    {
        $cardTypeMap = [
            '0' => '静态',
            '1' => '动态',
        ];

        return StatusService::formatMap($cardTypeMap);
    }
}