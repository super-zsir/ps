<?php

namespace Imee\Service\Operate\Topcard;

use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RoomTopCardConfigService
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
        $list = XsRoomTopCard::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('roomtopcardconfig', $ids);
        foreach ($list['data'] as &$item) {
            $item['icon_url'] = Helper::getHeadUrl($item['icon']);
            $item['operator'] = $logs[$item['id']]['operate_name'] ?? '-';
            $this->formatNameJson($item);
        }

        return $list;
    }

    private function formatNameJson(array &$data): void
    {
        $nameJson = json_decode($data['name_json'], true);
        $data['name'] = $nameJson['cn'] ?? '';

        foreach ($nameJson as $key => $item) {
            $data['name_' . $key] = $item;
        }
    }

    public function setEffectTimeMap()
    {
        return [
            ['label' => '10分钟', 'value' => 10],
            ['label' => '15分钟', 'value' => 15],
            ['label' => '20分钟', 'value' => 20],
            ['label' => '25分钟', 'value' => 25],
            ['label' => '30分钟', 'value' => 30],
        ];
    }

    public function create(array $params)
    {
        list($valid, $data) = $this->valid($params);
        if (!$valid) {
            return [$valid, $data];
        }
        list($res, $id) = $this->rpcService->createRoomTopCard($data);
        if (!$res) {
            return [$res, $id];
        }

        return [true, ['id' => $id, 'after_json' => $data]];
    }

    public function modify(array $params)
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            return [false, 'ID必传'];
        }
        $info = XsRoomTopCard::findOne($id);
        if (empty($info)) {
            return [false, '当前修改配置不存在'];
        }
        list($valid, $data) = $this->valid($params);
        if (!$valid) {
            return [$valid, $data];
        }
        $data['id'] = $id;
        list($res, $msg) = $this->rpcService->updateRoomTopCard($data);
        if (!$res) {
            return [$res, $msg];
        }

        return [true, ['id' => $id, 'before_json' => $info, 'after_json' => $data]];
    }

    public function delete(array $params): array
    {
        $id = $params['id'] ?? 0;
        if (empty($id)) {
            return [false, 'ID错误'];
        }

        list($res, $msg) = $this->rpcService->deleteRoomTopCard($id);
        if (!$res) {
            return [$res, $msg];
        }

        return [true, ['id' => $id, 'after_json' => []]];
    }

    private function valid(array $params): array
    {
        $cn = trim($params['name_cn'] ?? '');
        $en = trim($params['name_en'] ?? '');
        $icon = trim($params['icon'] ?? '');
        $effectTime = intval($params['effect_time'] ?? 0);

        if (empty($cn) || empty($en) || empty($icon) || empty($effectTime)) {
            return [false, '参数配置错误，必填项为全部填写'];
        }
        $nameJson = [];
        foreach ($params as $field => $value) {
            if (strpos($field, 'name_') !== false) {
                $key = str_replace('name_', '', $field);
                $nameJson[$key] = $value;
            }
        }

        $data = [
            'name_json' => json_encode($nameJson, JSON_UNESCAPED_UNICODE),
            'icon' => $icon,
            'effect_time' => $effectTime
        ];

        return [true, $data];
    }

    private function getConditions(array $params)
    {
        $conditions = [
            ['is_delete', '=', XsRoomTopCard::DELETE_NO]
        ];

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }

        return $conditions;
    }
}