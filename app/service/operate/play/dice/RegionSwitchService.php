<?php

namespace Imee\Service\Operate\Play\Dice;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
    public function getList(array $params, $order, $page, $pageSize): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, sic_bo_config', $order, $page, $pageSize);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('diceplayregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['id'];
            $v['switch'] = (string)(json_decode($v['sic_bo_config'], true)['switch'] ?? 0);
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
        list($res, $msg) = (new PsService())->setSicDoSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id' => $id,
            'switch' => json_decode($info['sic_bo_config'], true)['switch'] ?? 0
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            'switch' => $status
        ]]];
    }
}