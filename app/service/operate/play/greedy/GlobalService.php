<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xs\XsGreedyProfit;
use Imee\Models\Xs\XsGreedyRoundEngine;
use Imee\Models\Xs\XsGreedyUserLog;
use Imee\Service\Helper;

class GlobalService
{
    public function getList(array $params): array
    {
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $rec = $this->getBigareaData($params);
        } else {
            $rec = $this->getEngineData($params);
        }
        if (empty($rec['data'])) {
            return ['data' => [], 'total' => 0];
        }
        $roundIds = array_column($rec['data'], 'round_id');
        $conditions = [
            ['round_id', 'in', $roundIds]
        ];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $uids = XsGreedyUserLog::getUidByRoundIds($roundIds, $params['bigarea_id']);
            $conditions[] = ['uid', 'in', array_unique($uids)];
        }

        $data = $this->formatList($rec['data'], $conditions);

        return ['data' => $data, 'total' => $rec['total'] ?? []];
    }

    public function formatList($list, $conditions)
    {
        if (empty($list)) {
            return [];
        }
        $generator = XsGreedyUserLog::getGeneratorListByWhere($conditions, 'round_id, extra');
        $roundIds = array_unique(array_column($list, 'round_id'));
        $profit = XsGreedyProfit::getGreedyProfitUserBatch($roundIds);
        foreach ($generator as $result) {
            foreach ($result as $item) {
                if (!isset($data[$item['round_id']])) {
                    foreach (XsGlobalConfig::$greedyFoodId as $value) {
                        $data[$item['round_id']][$value] = 0;
                    }
                }
                $betList = json_decode($item['extra'], true)['bet_list'] ?? [];
                $betList = array_column($betList, 'counter', 'id');

                foreach (XsGlobalConfig::$greedyFoodId as $id => $value) {
                    $data[$item['round_id']][$value] += $betList[$id] ?? 0;
                }
            }
        }
        $map = [];
        foreach ($list as $val) {
            $replenish = 0;
            if (isset($profit[$val['round_id']]) && $profit[$val['round_id']]['op'] == 2) {
                $replenish = $profit[$val['round_id']]['profit'];
            }
            $map[] = [
                'engine_id' => $val['engine_id'],
                'round_id' => "\t" . $val['round_id'],
                'dateline' => Helper::now($val['start_time']),
                'carrot' => $data[$val['round_id']]['carrot'] ?? 0,
                'corn' => $data[$val['round_id']]['corn'] ?? 0,
                'tomatoes' => $data[$val['round_id']]['tomatoes'] ?? 0,
                'cauliflower' => $data[$val['round_id']]['cauliflower'] ?? 0,
                'shrimp' => $data[$val['round_id']]['shrimp'] ?? 0,
                'drumstick' => $data[$val['round_id']]['drumstick'] ?? 0,
                'meat' => $data[$val['round_id']]['meat'] ?? 0,
                'fish' => $data[$val['round_id']]['fish'] ?? 0,
                'total' => $val['bet_money'],
                'result' => XsGlobalConfig::$greedyFoodId[$val['prize_id']] ?? '-',
                'rewards' => $val['prize'],
                'replenish' => $replenish,
                'prize_pool' => $val['prize_pool']
            ];
        }
        return $map;
    }

    public function getEngineData($params)
    {
        $condition = [
            ['state', '=', 3]
        ];
        if (!empty($params['round_id'])) {
            $condition[] = ['round_id', '=', $params['round_id']];
        }
        if (!empty($params['dateline_sdate']) && !empty($params['dateline_edate'])) {
            $condition[] = ['start_time', '>=', strtotime($params['dateline_sdate'])];
            $condition[] = ['start_time', '<', strtotime($params['dateline_edate']) + 86400];
        }
        if (isset($params['greedy_engine_id']) && is_numeric($params['greedy_engine_id'])) {
            $condition[] = ['engine_id', '=', $params['greedy_engine_id']];
        }
        return XsGreedyRoundEngine::getListAndTotal($condition, '*', 'start_time desc', $params['page'], $params['limit']);
    }

    public function getJoinConditions($params, $fromTableName, $toTableName)
    {
        $condition = [
            ["{$fromTableName}.state", '=', XsGreedyRoundEngine::END_STATE]
        ];

        if (!empty($params['round_id'])) {
            $condition[] = ["{$fromTableName}.round_id", '=', $params['round_id']];
        }
        if (!empty($params['dateline_sdate']) && !empty($params['dateline_edate'])) {
            $condition[] = ["{$fromTableName}.start_time", '>=', strtotime($params['dateline_sdate'])];
            $condition[] = ["{$fromTableName}.start_time", '<', strtotime($params['dateline_edate']) + 86400];
        }
        if (isset($params['greedy_engine_id']) && is_numeric($params['greedy_engine_id'])) {
            $condition[] = ["{$fromTableName}.engine_id", '=', $params['greedy_engine_id']];
        }
        if (!empty($params['bigarea_id'])) {
            $condition[] = ["{$toTableName}.bigarea_id", '=', $params['bigarea_id']];
        }

        return $condition;
    }

    public function getBigareaData($params)
    {
        $fromTableName = XsGreedyRoundEngine::getTableName();
        $toTableName = XsGreedyUserLog::getTableName();
        $condition = $this->getJoinConditions($params, $fromTableName, $toTableName);

        $joinCondition = "{$fromTableName}.round_id = {$toTableName}.round_id";

        return XsGreedyRoundEngine::getListJoinUserLog($condition, $joinCondition, "{$fromTableName}.start_time desc", $params['page'], $params['limit']);
    }

    public function export($params)
    {
        $list = $this->getList($params);

        if (!$list['data']) {
            return [];
        }

        return self::onAfterList($list['data']);
    }

    public static function onAfterList($list)
    {
        if (empty($list)) {
            return [];
        }

        $data = [];

        foreach ($list as $value) {
            $data[] = [
                array_get(XsBigarea::$greedyEngine, $value['engine_id'], $value['engine_id']),
                $value['round_id'],
                $value['dateline'],
                $value['carrot'],
                $value['corn'],
                $value['tomatoes'],
                $value['cauliflower'],
                $value['shrimp'],
                $value['drumstick'],
                $value['meat'],
                $value['fish'],
                $value['total'],
                $value['result'],
                $value['rewards'],
                $value['replenish'],
                $value['prize_pool'],
            ];
        }
        return $data;
    }
}