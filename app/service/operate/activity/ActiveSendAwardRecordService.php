<?php

namespace Imee\Service\Operate\Activity;

use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Xs\XsActRankCommodityLog;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Service\Helper;

class ActiveSendAwardRecordService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsActRankCommodityLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($list['data'], 'user_bid'), ['bid', 'bname'], 'bname');
        foreach ($list['data'] as &$item) {
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id'], ',');
            $item['user_bigarea_id'] = XsBigarea::formatBigAreaName($item['user_bigarea_id'], ',');
            $item['bname'] = $brokerList[$item['user_bid']] ?? '';
            if ($item['award_type'] == BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD) {
                $item['award_type'] = XsActRankCommodityLog::$propCardTypeMap[$item['extend_type'] ?? ''] ?? '';
            } else {
                $item['award_type'] = BbcActWheelLotteryReward::$rewardTypeAllMap[$item['award_type']] ?? '';
            }
            $item['dateline'] = Helper::now($item['dateline']);
        }
        return $list;
    }

    private function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['act_id']) && !empty($params['act_id'])) {
            $conditions[] = ['act_id', '=', $params['act_id']];
        }

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', 'FIND_IN_SET', $params['bigarea_id']];
        }

        if (!empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }

        if (isset($params['cid']) && !empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }

        return $conditions;
    }

}