<?php

namespace Imee\Service\Operate\Play\Roomrocket;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RoomRocketRegionSwitchService
{
    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, boom_rocket_switch', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('roomrocketregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['id'];
            $v['switch'] = $v['boom_rocket_switch'];
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $res;
    }

    public function modify(int $id, int $status)
    {
        $info = XsBigarea::findOne($id);
        if (empty($info)) {
            return [false, '当前大区不存在'];
        }
        $update = [
            'big_area_id' => (int) $id,
            'on' => (bool) $status,
        ];
        list($res, $msg) = (new PsService())->setRoomRocketSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id' => $id,
            'switch' => $info['boom_rocket_switch']
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            'switch' => $status
        ]]];
    }
}