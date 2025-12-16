<?php

namespace Imee\Service\Luckygift;

use Imee\Models\Xsst\XsstLuckyGiftGlobal;

class GlobalService
{
	public function getList(array $params)
	{
		$conditions = $this->getCondition($params);
		$res = XsstLuckyGiftGlobal::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
		$minTimes = [];
		foreach ($res['data'] as &$v) {
			$bigareaId = $v['bigarea_id'];
			if (!isset($minTimes[$bigareaId])) {
				$minTimes[$bigareaId] = $v['exec_date'];
			} else {
				if ($minTimes[$bigareaId] > $v['exec_date']) {
					$minTimes[$bigareaId] = $v['exec_date'];
				}
			}
			$v['exec_date'] = date('Y-m-d', $v['exec_date']);
			$v['user_win_rate'] = number_format($v['user_win_price'] / $v['user_gifts_price'] * 100, 2) . '%';
		}
		$netProfits = [];
		foreach ($minTimes as $bigareaId => $val) {
			$netProfits[$bigareaId] = XsstLuckyGiftGlobal::sumNetProfit($bigareaId, $val);
		}
		$reverse = array_reverse($res['data']);
		foreach ($reverse as &$v) {
			$v['net_profit'] = $netProfits[$v['bigarea_id']] + $v['system_win_price'];
			$netProfits[$v['bigarea_id']] = $v['net_profit'];
		}
		$res['data'] = array_reverse($reverse);
		return $res;
	}

	public function getCondition(array $params)
	{
		$conditions = [];
		if (!empty($params['bigarea_id'])) {
			$conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
		}
		if (!empty($params['exec_date_sdate']) && !empty($params['exec_date_edate'])) {
			$conditions[] = ['exec_date', '>=', strtotime($params['exec_date_sdate'])];
			$conditions[] = ['exec_date', '<', strtotime($params['exec_date_edate'])];
		}
		return $conditions;
	}
}