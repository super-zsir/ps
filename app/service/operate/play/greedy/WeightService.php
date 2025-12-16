<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class WeightService
{
    public function getList(array $params): array
    {
        $greedyEngineId = intval(array_get($params,'greedy_engine_id',0));//必须要默认值
        $res = XsGlobalConfig::getGreedyWeightConfigParams($greedyEngineId);
        $ids = XsGlobalConfig::getLogId(array_column($res, 'id'), $greedyEngineId);
        $logs = BmsOperateLog::getFirstLogList('greedyweight', $ids);
        foreach ($res as &$v) {
            $_id =  XsGlobalConfig::getLogId(intval($v['id']), $greedyEngineId);
            $v['admin_name'] = $logs[$_id]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$_id]['created_time']) ? Helper::now($logs[$_id]['created_time']) : '';
        }
        return $res;
    }

    public function modify(int $id, int $rate, int $greedyEngineId): array
    {
        $config = XsGlobalConfig::setGreedyWeightConfig($id, $rate, $greedyEngineId);
        [$res, $msg] = (new PsService())->setGreedyMeta($config);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['id' => XsGlobalConfig::getLogId($id, $greedyEngineId), 'after_json' => [
            'id' => $id,
            'hit_rate' => $rate,
            'greedy_engine_id' => $greedyEngineId,
            'name' => XsGlobalConfig::$greedyFoodId[$id] . ' ' . XsGlobalConfig::$greedyFoodCnName[$id]
        ]]];
    }
}