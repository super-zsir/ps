<?php

namespace Imee\Service\Operate\Activity\Firstcharge;

use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityReward;
use Imee\Models\Xs\XsTopUpActivityUserRewardFlow;
use Imee\Service\Helper;
use Imee\Service\StatusService;
use Imee\Models\Xs\XsActRankCommodityLog;

class FirstChargeAwardRecordService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsTopUpActivityUserRewardFlow::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($list['data'], 'user_bid'), ['bid', 'bname'], 'bname');
        foreach ($list['data'] as &$item) {
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id'], ',');
            $item['bname'] = $brokerList[$item['user_bid']] ?? '';
            if ($item['award_type'] == BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD) {
                $item['award_type'] = XsActRankCommodityLog::$propCardTypeMap[$item['extend_type'] ?? ''] ?? '';
            } else {
                $item['award_type'] = BbcActWheelLotteryReward::$rewardTypeAllMap[$item['award_type']] ?? '';
            }
            $item['dateline'] = Helper::now($item['dateline']);
            $item['reward_level'] = $item['reward_level'] ? (XsTopUpActivityReward::$rewardLevelMap[$item['reward_level']] ?? '') : '';
        }
        return $list;
    }

    public function getRewardLevelMap()
    {
        return StatusService::formatMap(XsTopUpActivityReward::$rewardLevelMap, 'label,value');
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['act_type', '=', XsTopUpActivity::TYPE_FIRST_RECHARGE]
        ];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }

        if (isset($params['cid']) && !empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }

        if (isset($params['reward_level']) && !empty($params['reward_level'])) {
            $conditions[] = ['reward_level', '=', $params['reward_level']];
        }

        return $conditions;
    }
}