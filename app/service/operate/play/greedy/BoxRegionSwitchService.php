<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class BoxRegionSwitchService
{
    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, greedy_box_switch, greedy_engine_id', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('greedyboxregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['id'];
            $v['switch'] = $v['greedy_box_switch'];
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
            'big_area_id' => $id,
            'switch' => $status
        ];
        list($res, $msg) = (new PsService())->setGreedyBoxSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id' => $id,
            'switch' => $info['greedy_box_switch']
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            'switch' => $status
        ]]];
    }
}