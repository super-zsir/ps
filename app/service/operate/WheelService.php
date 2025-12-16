<?php

/**
 * 摩天轮
 */
namespace Imee\Service\Operate;

use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\XsGreedyRoundPlayerV2;
use Imee\Models\Xs\XsGreedyRound;
use Imee\Models\Xs\XsUserProfile;

class WheelService
{
	use SingletonTrait;

	public function getTagListAndTotal($params, $order = '', $page = 0, $pageSize = 0)
	{
		$filter = [];
		$uid = intval($params['uid'] ?? 0);
		$roundId = trim($params['round_id'] ?? 0);
		$moneyType = trim($params['money_type'] ?? '');
		$start = trim($params['start'] ?? 0);
		$end = trim($params['end'] ?? 0);

		if ($uid > 0) {
			$filter[] = ['uid', '=', $uid];
		}

		if ($roundId > 0) {
			$filter[] = ['round_id', '=', $roundId];
		}

		if (!empty($moneyType)) {
			$filter[] = ['money_type', '=', $moneyType];
		}

		if (!empty($start)) {
			$start = strtotime($start);
			$filter[] = ['dateline', '>=', $start];
		}

		if (!empty($end)) {
			$end = strtotime($end . ' 23:59:59');
			$filter[] = ['dateline', '<=', $end];
		}

		$result = XsGreedyRoundPlayerV2::getListAndTotal($filter, '*', $order, $page, $pageSize);
		if (0 == $result['total']){
			return ['total'=>0,'data'=>[]];
		}
		$uids = array_values(array_unique(array_column($result['data'],'uid')));
		$userList = XsUserProfile::getUserProfileBatch($uids);
		$roundIds = array_values(array_unique(array_column($result['data'],'round_id')));
		$greedyList = XsGreedyRound::getBatchCommon($roundIds, ['prize_id', 'id','prize_type'], 'id');
		foreach ($result['data'] as &$v) {
			$v['prize_id'] = $greedyList[$v['round_id']]['prize_id'] ?? '-';
			$v['prize_type'] = $greedyList[$v['round_id']]['prize_type'] ?? '-';
			$v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : ' - ';
			$v['name'] = $userList[$v['uid']]['name'] ?? '-';
		}
		return $result;
	}
}