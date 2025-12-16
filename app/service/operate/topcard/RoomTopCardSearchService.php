<?php

namespace Imee\Service\Operate\Topcard;

use Imee\Models\Xs\XsUserRoomTopCard;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RoomTopCardSearchService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params, int $page, int $pageSize)
    {
        $list = XsUserRoomTopCard::getListJoinTable(
            $this->getConditions($params),
            $this->getColumns(),
            'r.uid desc',
            $page,
            $pageSize
        );
        if ($list['total'] == 0) {
            return [];
        }
        return $list;
    }

    private function getColumns()
    {
        return [
            'r.uid',
            'sum(if(expired_time >= UNIX_TIMESTAMP(), num, 0)) as hold_num',
            'sum(if(expired_time < UNIX_TIMESTAMP(), num, 0)) as expire_num',
            'b.bigarea_id',
            'c.effect_time',
            'r.room_top_card_id'
        ];
    }

    public function recover(array $params)
    {
        list($valid, $data) = $this->valid($params);
        if (!$valid) {
            return [$valid, $data];
        }
        list($res, $msg) = $this->rpcService->recoverRoomTopCard($data);
        if (!$res) {
            return [$res, $msg];
        }

        return [true, ['after_json' => $data]];
    }

    private function valid(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $holdNum = intval($params['hold_num'] ?? 0);
        $num = intval($params['num'] ?? 0);
        $cid = intval($params['room_top_card_id'] ?? 0);

        if (empty($cid) || empty($uid)) {
            return [false, '参数配置错误'];
        }
        if ($num > $holdNum) {
            return [false, '数字填写不能大于背包已有数量'];
        }

        $data = [
            'room_top_card_id' => $cid,
            'num' => $num,
            'uid' => $uid,
        ];

        return [true, $data];
    }

    private function getConditions(array $params)
    {
        $conditions = [];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['r.uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['r.dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['r.dateline', '<', strtotime($params['dateline_edate']) + 86400];
        }

        return $conditions;
    }
}