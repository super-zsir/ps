<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, greedy_config, greedy_engine_id, greedy_global_rank_switch', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('greedyplayregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['id'];
            $v['switch'] = (string)(json_decode($v['greedy_config'], true)['greedy_switch'] ?? 2);
            $v['global_rank_switch'] = $v['greedy_global_rank_switch'] ?? 0;
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $res;
    }

    public function modify(array $params): array
    {
        $info = XsBigarea::findOne($params['id']);
        if (empty($info)) {
            return [false, '当前大区不存在'];
        }
        $update = [
            'big_area_id' => (int)$params['id'],
            'switch'      => (int)$params['switch']
        ];
        $service =  new PsService();
        list($res, $msg) = $service->setGreedySwitch($update);
        if (!$res) {
            return [false, $msg];
        }

        $updateEngin = [
            'big_area_id' => (int)$params['id'],
            'engine_id'   => (int)$params['greedy_engine_id'],
        ];
        list($res, $msg) = $service->setGreedyEngine($updateEngin);
        if (!$res) {
            return [false, $msg];
        }

        $updateRankSwitch = [
            'big_area_id' => (int)$params['id'],
            'switch'      => (int)$params['global_rank_switch'],
        ];
        list($res, $msg) = $service->setGreedyGlobalRankSwitch($updateRankSwitch);
        if (!$res) {
            return [false, $msg];
        }

        $beforeJson = [
            'id'                        => $params['id'],
            'switch'                    => json_decode($info['greedy_config'], true)['greedy_switch'] ?? 2,
            'greedy_global_rank_switch' => $info['greedy_global_rank_switch'],
            'greedy_engine_id'          => array_get($info, 'greedy_engine_id', XsBigarea::GREEDY_START_A)
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id'                        => $params['id'],
            'switch'                    => $params['switch'],
            'greedy_global_rank_switch' => $params['global_rank_switch'],
            'greedy_engine_id'          => $params['greedy_engine_id'],
        ]]];

    }
}