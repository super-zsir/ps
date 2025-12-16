<?php

namespace Imee\Service\Operate\Chatroom;

use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\XssRoomLog;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class ChatroomPublicScreenMsgService
{
    /**
     * @var XssRoomLog $model
     */
    private $model = XssRoomLog::class;

    /**
     * @var XsUserProfile $userModel
     */
    private $userModel = XsUserProfile::class;

    /**
     * @var XsChatroom $chatroomModel
     */
    private $chatroomModel = XsChatroom::class;

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        $userList = $this->userModel::getUserProfileBatch(Helper::arrayFilter($list['data'], 'uid'));
        $chatroomList = $this->chatroomModel::getInfoBatch(Helper::arrayFilter($list['data'], 'rid'), ['rid', 'name']);

        foreach ($list['data'] as &$item) {
            $item['uname'] = ($userList[$item['uid']] ?? [])['name'] ?? '';
            $item['rname'] = ($chatroomList[$item['rid']] ?? [])['name'] ?? '';
            $item['dateline'] = $item['dateline'] ? Helper::now($item['dateline']) : '';
            $extraJson = json_decode($item['extra'], true);
            if ($extraJson) {
                if ($extraJson['type'] == $this->model::TYPE_PACKAGE) {
                    $item['extra'] = 'uid: ' . $extraJson['uid'] . ', price:' . $extraJson['price'];
                } else if ($extraJson['type'] == $this->model::TYPE_NOTIFY) {
                    $item['extra'] = 'uid: ' . $extraJson['uid'];
                }
            }

        }

        return $list;
    }

    private function getConditions(array $params): array
    {
        $conditions = [];

        $rid = intval($params['rid'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $type = trim($params['type'] ?? '');
        $startTime = trim($params['start_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');

        $rid && $conditions[] = ['rid', '=', $rid];
        $uid && $conditions[] = ['uid', '=', $uid];
        $type && $conditions[] = ['type', '=', $type];
        $startTime && $conditions[] = ['dateline', '>=', strtotime($startTime)];
        $endTime && $conditions[] = ['dateline', '<=', strtotime($endTime)];

        return $conditions;
    }

    public function getTypeMap()
    {
        return StatusService::formatMap($this->model::$typeMap, 'label,value');
    }
}