<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class ParamsService
{
    public function getList(array $params): array
    {
        $greedyEngineId = intval(array_get($params,'greedy_engine_id',0));//必须要默认值
        $res = XsGlobalConfig::getGreedyConfigParams($greedyEngineId);
        $ids = XsGlobalConfig::getLogId(array_column($res, 'id'), $greedyEngineId);
        $logs = BmsOperateLog::getFirstLogList('greedyparams', $ids);
        foreach ($res as &$v) {
            $_id =  XsGlobalConfig::getLogId(intval($v['id']), $greedyEngineId);
            $v['admin_name'] = $logs[$_id]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$_id]['created_time']) ? Helper::now($logs[$_id]['created_time']) : '';
        }
        return $res;
    }

    public function modify(int $id, int $number, int $greedyEngineId): array
    {
        $config = XsGlobalConfig::findByType(XsGlobalConfig::getGreedyType($greedyEngineId));
        XsGlobalConfig::setParamsValue($id, $config);
        if ($id == 2) {
            if ($number < 2000000) {
                return [false, '利润分割线必须大于200万'];
            }
            if ($number < intval($config['profit_money']) * 2) {
                return [false, '利润分割线必须大于利润分割金额的两倍'];
            }
        } else if ($id == 1 && $number != 0) {
            return [false, '是否初始化的数值只能为0'];
        } else if ($id == 3 && $number > intval($config['profit_line']) * 0.5) {
            return [false, '利润分割金额不可超过利润分割线的50%'];
        } else if (($id == 100 || $id == 101) && $number > intval($config['profit_line'])) {
            return [false, '大奖线不能超过利润分割线'];
        } else if (($id == 12 || $id == 14) && $number < 30) {
            return [false, '冷却间隔为大于等于30的自然数'];
        } else if (($id == 13 && $number * 2 > $config['pizza_cd_interval']) || ($id == 12 && $number < $config['pizza_random_interval'] * 2)) {
            return [false, '披萨随机区间不可超过披萨冷却时间的1/2'];
        } else if (($id == 15 && $number * 2 > $config['salad_cd_interval']) || ($id == 14 && $number < $config['salad_random_interval'] * 2)) {
            return [false, '沙拉随机区间不可超过沙拉冷却时间的1/2'];
        }

        $comment = XsGlobalConfig::modifyGreedyParamsConfig($id, $number, $greedyEngineId);
        [$res, $msg] = (new PsService())->setGreedyMeta($comment);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['id' => XsGlobalConfig::getLogId($id, $greedyEngineId), 'after_json' => [
            'id' => $id,
            'number' => $number,
            'greedy_engine_id' => $greedyEngineId,
        ]]];
    }
}