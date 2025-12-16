<?php

namespace Imee\Service\Operate\Play\Teenpatti;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
    public function getList(array $params, $order, $page, $pageSize): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, teenpatti_config', $order, $page, $pageSize);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('teenpattiplayregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['id'];
            $v['switch'] = (string)(json_decode($v['teenpatti_config'], true)['switch'] ?? 0);
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
            'bigarea_id' => $id,
            'switch'      => $status
        ];
        list($res, $msg) = (new PsService())->setTeenPattiSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id'     => $id,
            'switch' => json_decode($info['teenpatti_config'], true)['switch'] ?? 0
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id'     => $id,
            'switch' => $status
        ]]];
    }
}