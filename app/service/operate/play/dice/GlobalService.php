<?php

namespace Imee\Service\Operate\Play\Dice;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xs\XsSicBoEngine;
use Imee\Models\Xs\XsSicBoProfit;
use Imee\Models\Xs\XsSicBoUserBetLog;
use Imee\Models\Xs\XsSicBoUserLog;
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
            $uids = XsSicBoUserLog::getUidByRoundIds($roundIds, $params['bigarea_id']);
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
        $generator = XsSicBoUserBetLog::getGeneratorListByWhere($conditions);
        $roundIds = array_column($list, 'round_id');
        $profit = XsSicBoProfit::getSicBoProfitUserBatch($roundIds);
        $data = [];
        foreach ($generator as $result) {
            foreach ($result as $item) {
                if (!isset($data[$item['round_id']])) {
                    $data[$item['round_id']] = [
                        '1' => 0,
                        '2' => 0,
                        '3' => 0
                    ];
                }
                $data[$item['round_id']][$item['bet_id']] += $item['chip_id'] * $item['count'];
            }
        }
        $map = [];
        foreach ($list as $val) {
            $replenish = 0;
            if (isset($profit[$val['round_id']]) && $profit[$val['round_id']]['op'] == 2) {
                $replenish = $profit[$val['round_id']]['profit'];
            }
            $map[] = [
                'round_id' => "\t" . $val['round_id'],
                'dateline' => Helper::now(substr($val['start_time'], 0, -3)),
                'small' => $data[$val['round_id']]['1'] ?? 0,
                'big' => $data[$val['round_id']]['2'] ?? 0,
                'triple' => $data[$val['round_id']]['3'] ?? 0,
                'result' => XsGlobalConfig::$sicBoConfig[$val['prize_id']] ?? '-',
                'rewards' => $val['prize'],
                'total' => $val['bet_money'],
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
            $condition[] = ['start_time', '>=', strtotime($params['dateline_sdate']) * 1000];
            $condition[] = ['start_time', '<', strtotime($params['dateline_edate']) * 1000];
        }
        return XsSicBoEngine::getListAndTotal($condition, '*', 'start_time desc', $params['page'], $params['limit']);
    }

    public function getJoinConditions($params, $fromTableName, $toTableName)
    {
        $condition = [
            ["{$fromTableName}.state", '=', XsSicBoEngine::END_STATE]
        ];

        if (!empty($params['round_id'])) {
            $condition[] = ["{$fromTableName}.round_id", '=', $params['round_id']];
        }
        if (!empty($params['dateline_sdate']) && !empty($params['dateline_edate'])) {
            $condition[] = ["{$fromTableName}.start_time", '>=', strtotime($params['dateline_sdate']) * 1000];
            $condition[] = ["{$fromTableName}.start_time", '<', strtotime($params['dateline_edate']) * 1000];
        }
        if (!empty($params['bigarea_id'])) {
            $condition[] = ["{$toTableName}.bigarea_id", '=', $params['bigarea_id']];
        }

        return $condition;
    }

    public function getBigareaData($params)
    {
        $fromTableName = XsSicBoEngine::getTableName();
        $toTableName = XsSicBoUserLog::getTableName();
        $condition = $this->getJoinConditions($params, $fromTableName, $toTableName);

        $joinCondition = "{$fromTableName}.round_id = {$toTableName}.round_id";

        return XsSicBoEngine::getListJoinUserLog($condition, $joinCondition, "{$fromTableName}.start_time desc", $params['page'], $params['limit']);
    }
}