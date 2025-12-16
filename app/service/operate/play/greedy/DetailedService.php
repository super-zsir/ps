<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xs\XsGreedyRoundEngine;
use Imee\Models\Xs\XsGreedyUserLog;
use Imee\Service\Helper;

class DetailedService
{
    public function getList(array $params): array
    {
        $fromTableName = XsGreedyUserLog::getTableName();
        $toTableName = XsGreedyRoundEngine::getTableName();
        $condition = $this->getJoinConditions($params, $fromTableName, $toTableName);
        $joinCondition = "{$fromTableName}.round_id = {$toTableName}.round_id";
        $res = XsGreedyUserLog::getListJoinRoundEngine($condition, $joinCondition, "{$fromTableName}.dateline desc", $params['page'], $params['limit']);

        foreach ($res['data'] as &$v) {
            $v['round_id'] = "\t" . $v['round_id'];
            $v['dateline'] = Helper::now($v['dateline']) ?? 0;
            $v['prize_id'] = XsGlobalConfig::$greedyFoodId[$v['prize_id']] ?? '-';
            $extra = json_decode($v['extra'], true)['bet_list'] ?? [];
            $extra = array_column($extra, 'counter', 'id');
            foreach (XsGlobalConfig::$greedyFoodId as $key => $value) {
                $v[$value] = $extra[$key] ?? 0;
            }
        }
        return $res;
    }

    public function getJoinConditions($params, $fromTableName, $toTableName): array
    {
        $conditions = [];

        if (!empty($params['uid'])) {
            $conditions[] = ["{$fromTableName}.uid", '=', $params['uid']];
        }

        $startTime = strtotime($params['dateline_sdate'] ?? '');
        $endTime = strtotime($params['dateline_edate'] ?? '');

        // uid 筛选不存在，则 必须筛选时间且时间区间不能大于3天
        if (empty($conditions)) {
            if (empty($startTime) || empty($endTime)) {
                throw new ApiException(ApiException::MSG_ERROR, 'Time/时间必须筛选');
            }
            if (($endTime - $startTime) > (2 * 86400)) {
                throw new ApiException(ApiException::MSG_ERROR, 'Time/时间区间不能大于3天');
            }
        }

        if (!empty($startTime) && !empty($endTime)) {
            $conditions[] = ["{$fromTableName}.dateline", '>=', $startTime];
            $conditions[] = ["{$fromTableName}.dateline", '<', $endTime + 86400];
        }

        if (!empty($params['greedy_engine_id'])) {
            $conditions[] = ["{$toTableName}.engine_id", '=', $params['greedy_engine_id']];
        }

        if (!empty($params['round_id'])) {
            $conditions[] = ["{$fromTableName}.round_id", '=', $params['round_id']];
        }
        
        return $conditions;
    }
}