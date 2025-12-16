<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class BoxParamsService
{
    public function getList(array $params): array
    {
        $greedyEngineId = intval(array_get($params, 'greedy_engine_id', 0));//必须要默认值
        $res = XsGlobalConfig::getGreedyBoxConfigParams($greedyEngineId);
        $ids = XsGlobalConfig::getLogId(array_column($res, 'id'), $greedyEngineId);

        $logs = BmsOperateLog::getFirstLogList('greedyboxparams', $ids);
        foreach ($res as &$v) {
            $_id =  XsGlobalConfig::getLogId(intval($v['id']), $greedyEngineId);
            $v['admin_name'] = $logs[$_id]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$_id]['created_time']) ? Helper::now($logs[$_id]['created_time']) : '';
        }
        return $res;
    }

    public function modify(int $id, int $number, int $greedyEngineId): array
    {
        $comment = XsGlobalConfig::modifyGreedyBoxParamsConfig($id, $number, $greedyEngineId);
        [$res, $msg] = (new PsService())->setGreedyMeta($comment);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['id' => XsGlobalConfig::getLogId(intval($id), $greedyEngineId), 'after_json' => [
            'id' => $id,
            'number' => $number,
            'greedy_engine_id' => $greedyEngineId,
        ]]];
    }
}