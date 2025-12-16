<?php

namespace Imee\Service\Operate\Chatroom;

use Imee\Exception\ApiException;
use Imee\Libs\Utility;
use Imee\Models\Xs\XsChatroomSetredpackage;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class ChatroomActivityRedPacketService
{
    /**
     * @var XsChatroomSetredpackage $model
     */
    private $model = XsChatroomSetredpackage::class;

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['begin_time'] = $item['begin_time'] ? Helper::now($item['begin_time']) : '';
            $item['end_time'] = $item['end_time'] ? Helper::now($item['end_time']) : '';
            $item['online_time'] = $item['begin_time'] . ' ... ' . $item['end_time'];
            $item['dateline'] = $item['dateline'] ? Helper::now($item['dateline']) : '';
            $item['icon_all'] = Helper::getHeadUrl($item['icon']);
        }

        return $list;
    }

    public function create(array $params): array
    {
        $name = trim($params['name'] ?? '');
        $deleted = intval($params['deleted'] ?? 1);
        $ordering = intval($params['ordering'] ?? 100);
        $icon = trim($params['icon'] ?? '');
        $beginTime = trim($params['begin_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');

        $data = [
            'name'       => $name,
            'deleted'    => $deleted,
            'ordering'   => $ordering,
            'icon'       => $icon,
            'dateline'   => time(),
            'begin_time' => $beginTime ? strtotime($beginTime) : 0,
            'end_time'   => $endTime ? strtotime($endTime) : 0,
        ];

        list($res, $msg) = $this->model::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '添加失败，原因：' . $msg);
        }


        return ['id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $name = trim($params['name'] ?? '');
        $deleted = intval($params['deleted'] ?? 1);
        $ordering = intval($params['ordering'] ?? 100);
        $icon = trim($params['icon'] ?? '');
        $beginTime = trim($params['begin_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');

        $info = $this->model::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室活动红包不存在');
        }

        $data = [
            'name'       => $name,
            'deleted'    => $deleted,
            'ordering'   => $ordering,
            'icon'       => $icon,
            'begin_time' => $beginTime ? strtotime($beginTime) : 0,
            'end_time'   => $endTime ? strtotime($endTime) : 0,
        ];

        list($res, $msg) = $this->model::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '编辑失败，原因：' . $msg);
        }

        return ['id' => $id, 'after_json' => $data, 'before_json' => $info];
    }

    private function getConditions(array $params): array
    {
        $conditions = [];
        $id = intval($params['id'] ?? 0);

        $id && $conditions[] = ['id', '=', $id];

        return $conditions;
    }

    public function getDeletedMap()
    {
        return StatusService::formatMap($this->model::$deletedMap, 'label,value');
    }
}