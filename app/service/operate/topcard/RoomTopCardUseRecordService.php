<?php

namespace Imee\Service\Operate\Topcard;

use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsRoomTopCardUseRecord;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RoomTopCardUseRecordService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params)
    {
        $conditions = $this->getConditions($params);
        $list = XsRoomTopCardUseRecord::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        $uid = array_column($list['data'], 'uid');
        $rid = array_column($list['data'], 'rid');
        $roomList = XsChatroom::getInfoBatch($rid);
        $ruid = array_column($roomList, 'uid');
        $uid = array_merge($uid, $ruid);
        $bigAreaList = XsUserBigarea::getUserBigareas($uid);
        $cid = array_column($list['data'], 'room_top_card_id');
        $cardList = XsRoomTopCard::getBatchCommon($cid, ['id', 'effect_time']);
        foreach ($list['data'] as &$item) {
            $item['effect_time'] = $cardList[$item['room_top_card_id']]['effect_time'] ?? '';
            $roomUid = $roomList[$item['rid']]['uid'] ?? 0;
            $item['bigarea_id'] = $bigAreaList[$item['uid']] ?? '';
            $item['rbigarea_id'] = $bigAreaList[$roomUid] ?? '';
            $item['dateline'] = date('Y-m-d H:i', $item['dateline']);
        }
        return $list;
    }

    private function getConditions(array $params)
    {
        $conditions = [];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['rid']) && !empty($params['rid'])) {
            $conditions[] = ['rid', 'IN', Helper::formatIdString($params['rid'])];
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