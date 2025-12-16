<?php

namespace Imee\Service\Luckygift;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsLuckyGiftUserLog;
use Imee\Service\Helper;

class DetailedService
{
    public function getList(array $params)
    {
        $conditions = $this->getCondition($params);
        $res = XsLuckyGiftUserLog::getListAndTotal($conditions, 'uid, bigarea_id, dateline, gift_id, gift_name, gift_price, bet, gift_num, prize', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        foreach ($res['data'] as &$v) {
            $v['bet'] = $v['gift_num'] * $v['bet'];
            $v['profit'] = $v['bet'] - $v['prize'];
            $v['dateline'] = $v['dateline'] > 0 ? Helper::now($v['dateline']) : '';
        }
        return $res;
    }

    public function getCondition(array $params)
    {
        $conditions = [
            ['status', '=', 1],
        ];
        if (!empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }

        if (!empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }
        if (!empty($params['dateline_sdate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate'])];
        }
        return $conditions;
    }
}