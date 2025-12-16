<?php

namespace Imee\Service\Operate\Play\Dice;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xs\XsSicBoUserBetLog;
use Imee\Models\Xs\XsSicBoUserLog;
use Imee\Service\Helper;

class DetailedService
{
    public function getList(array $params): array
    {
        $condstion = $this->getCondition($params);
        $res = XsSicBoUserLog::getListAndTotal($condstion, '*', 'dateline desc', $params['page'], $params['limit']);
        foreach ($res['data'] as &$v) {
            $betInfo = XsSicBoUserBetLog::getBetListByWhere([
                ['uid', '=', $v['uid']],
                ['round_id', '=', $v['round_id']]
            ]);
            $v['small'] = $betInfo['1'];
            $v['big'] = $betInfo['2'];
            $v['triple'] = $betInfo['3'];
            $v['round_id'] = "\t" . $v['round_id'];
            $v['dateline'] = Helper::now($v['dateline']) ?? 0;
            $v['total'] = $v['bet_money'] ?? 0;
            $v['result'] = XsGlobalConfig::$sicBoConfig[$v['prize_id']] ?? '-';
            $v['rewards'] = $v['prize'] ?? 0;

        }
        return $res;
    }

    public function getCondition($params)
    {
        $conditions = [];
        if (!empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['round_id'])) {
            $conditions[] = ['round_id', '=', $params['round_id']];
        }
        if (!empty($params['dateline_sdate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86400];
        }
        return $conditions;
    }
}