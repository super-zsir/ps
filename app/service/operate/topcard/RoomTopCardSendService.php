<?php

namespace Imee\Service\Operate\Topcard;

use Imee\Models\Xs\XsSendRoomTopCardLog;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RoomTopCardSendService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params, $source = XsSendRoomTopCardLog::SOURCE_GRANT)
    {
        $conditions = $this->getConditions($params, $source);

        $list = XsSendRoomTopCardLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        foreach ($list['data'] as &$item) {
            $item['send_num'] = $item['num'];
            // 4294967295 表示永久有效
            if ($item['expired_time'] != 4294967295) {
                $effectDay = ceil(($item['expired_time'] - $item['dateline']) / 86400);
                $item['expired_time'] = max($effectDay, 0);
            } else {
                $item['expired_time'] = '永久';
            }
            $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
        }
        return $list;
    }

    public function setTopCardMap()
    {
        $list = (new RoomTopCardConfigService())->getList([]);
        if (empty($list['data'])) {
            return [];
        }
        $map = [];
        foreach ($list['data'] as $item) {
            $map[] = [
                'label' => "【ID:{$item['id']}】" . $item['name'],
                'value' => $item['id'],
            ];
        }
        return $map;
    }

    public function create(array $params)
    {
        list($valid, $data) = $this->valid($params);
        if (!$valid) {
            return [$valid, $data];
        }
        $admin = Helper::getAdminName($params['admin_id']);
        list($res, $msg) = $this->rpcService->sendRoomTopCard([$data], $admin);
        if (!$res) {
            return [$res, $msg];
        }

        return [true, ['after_json' => $data]];
    }

    public function addBatch(array $params)
    {
        $list = [];
        foreach ($params as $item) {
            [$valid, $data] = $this->valid($item);
            if (!$valid) {
                return [$valid, $data];
            }
            $list[] = $data;
        }
        $admin = Helper::getAdminName(Helper::getSystemUid());
        list($res, $msg) = $this->rpcService->sendRoomTopCard($list, $admin);
        if (!$res) {
            return [$res, $msg];
        }

        return [true, ['after_json' => $list]];
    }

    private function valid(array $params): array
    {
        $uid = trim($params['uid'] ?? '');
        $uidArr = Helper::formatIdString($uid);
        $cid = intval($params['room_top_card_id'] ?? 0);
        $num = intval($params['num'] ?? '');
        $expiredTime = intval($params['expired_time'] ?? 0);
        $remark = trim($params['remark'] ?? '');

        if (empty($uidArr) || empty($cid) || empty($num)) {
            return [false, '参数配置错误'];
        }
        if ($num > 10000) {
            return [false, '超出发放数量最大限制10000'];
        }
        if (!is_numeric($params['expired_time']) || $expiredTime < 0) {
            return [false, '有效期必须填写整数且不得小于0'];
        }

        $errorUid = XsUserProfile::checkUid($uidArr);

        if ($errorUid && is_array($errorUid)) {
            return [false, implode(',', $errorUid) . '以上UID错误'];
        }
        $data = [
            'uids'             => implode(',', $uidArr),
            'room_top_card_id' => $cid,
            'num'              => $num,
            'expire_time'      => $expiredTime,
            'remark'           => $remark,
        ];

        return [true, $data];
    }

    private function getConditions(array $params, $source = XsSendRoomTopCardLog::SOURCE_GRANT)
    {
        $conditions = [['source', '=', $source]];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86400];
        }

        return $conditions;
    }
}