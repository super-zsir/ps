<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskUser;

use Imee\Helper\Constant\RiskConstant;
use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Service\Domain\Context\Audit\RiskUser\RiskStrategyStatisticsContext;

class RiskStrategyStatisticsProcess
{
	public function __construct(RiskStrategyStatisticsContext $context)
	{
		$this->context = $context;
	}

	public function handle()
	{
		if (empty($this->context->start)) {
			$start = strtotime(date('Y-m-d', strtotime('today')));
		} else {
			$start = strtotime($this->context->start);
		}
		$end = empty($this->context->end) ? strtotime(date('Y-m-d', strtotime('today'))) + 86400 : strtotime($this->context->end) + 86400;

		$process = new RiskUserProcess();
		$data = $process->handleStatistics($start, $end, $this->context->ruleType);

		//获取【封禁核查】中的封禁总数
		$all_num = $this->getForbiddenNum($start, $end);

		$forbidden_all_nums = [];
		if ($all_num) {
			foreach ($all_num as $val) {
				$forbidden_all_nums[$val['day']] = $val['num'];
			}
		}
		unset($all_num);

		$count = array(
			'rule_name' => '合计',
			'rule_type' => 'all',
			'total' => 0,
			'unhandle' => 0,
			'handled' => 0,
			'correct_catch' => 0,
			'forbidden_all_num' => 0,
			'forbidden' => 0,
			'forbidden_forever' => 0,
			'forbidden_normal' => 0,
			'forbidden_god' => 0,
			'forbidden_fresh' => 0,
			'create_time' => '-',
		);
		$res = array();
		foreach ($data as $day => $val) {
			$forbidden_all_num = $forbidden_all_nums[$day] ?? 0;
			$count['forbidden_all_num'] += $forbidden_all_num;
			foreach ($val as $k => $v) {
				$temp = [
					'create_time' => $day,
					'rule_name' => RiskConstant::RISK_USER_RULE_TYPES[$k] ?? '规则:' . $k,
					'rule_type' => $k,
					'total' => $v['total'],
					'unhandle' => $v['unhandle'],
					'handled' => $v['handled'],
					'correct_catch' => $v['correct_catch'],
					'forbidden_all_num' => $forbidden_all_num,
					'forbidden' => $v['forbidden'],
					'forbidden_normal' => $v['forbidden_normal'],
					'forbidden_god' => $v['forbidden_god'],
					'forbidden_fresh' => $v['forbidden_fresh'],
					'forbidden_user' => $v['forbidden'] - $v['forbidden_god'],
					'forbidden_forever' => $v['forbidden_forever'],
					'accuracy' => $v['handled'] > 0 ? sprintf('%.4f', $v['forbidden'] / $v['handled']) * 100 ."%" : 0 ."%",
					'recall_rate' => $forbidden_all_num > 0 ? sprintf('%.4f', $v['forbidden'] / $forbidden_all_num) * 100 ."%" : 0 ."%",
					'forbidden_forever_rate' => $v['forbidden'] > 0 ? sprintf('%.4f', $v['forbidden_forever'] / $v['forbidden']) * 100 ."%" : 0 ."%",
					'forbidden_normal_rate' => $v['forbidden'] > 0 ? sprintf('%.4f', $v['forbidden_normal'] / $v['forbidden']) * 100 ."%" : 0 ."%",
					'correct_catch_rate' => $v['total'] > 0 ? sprintf('%.4f', $v['correct_catch'] / $v['total']) * 100 ."%" : 0 ."%",
				];
				array_push($res, $temp);

				$count['total'] += $v['total'];
				$count['unhandle'] += $v['unhandle'];
				$count['handled'] += $v['handled'];
				$count['correct_catch'] += $v['correct_catch'];
				$count['forbidden'] += $v['forbidden'];
				$count['forbidden_forever'] += $v['forbidden_forever'];
				$count['forbidden_normal'] += $v['forbidden_normal'];
				$count['forbidden_god'] += $v['forbidden_god'];
				$count['forbidden_fresh'] += $v['forbidden_fresh'];
			}
		}

		$count['forbidden_user'] = $count['forbidden'] - $count['forbidden_god'];
		$count['accuracy'] = $count['handled'] > 0 ? sprintf('%.4f', $count['forbidden'] / $count['handled']) * 100 ."%" : 0 ."%";
		$count['recall_rate'] = $count['forbidden_all_num'] > 0 ? sprintf('%.4f', $count['forbidden'] / $count['forbidden_all_num']) * 100 ."%" : 0 ."%";
		$count['forbidden_forever_rate'] = $count['forbidden'] > 0 ? sprintf('%.4f', $count['forbidden_forever'] / $count['forbidden']) * 100 ."%" : 0 ."%";
		$count['forbidden_normal_rate'] = $count['forbidden'] > 0 ? sprintf('%.4f', $count['forbidden_normal'] / $count['forbidden']) * 100 ."%" : 0 ."%";
		$count['correct_catch_rate'] = $count['total'] > 0 ? sprintf('%.4f', $count['correct_catch'] / $count['total']) * 100 ."%" : 0 ."%";
		array_unshift($res, $count);

		return [
			'data' => $res,
			'total' => count($res)
		];
	}

	private function getForbiddenNum($start, $end)
	{
		$start = intval($start);
		$end = intval($end);
		if ($start <= 0 || $end <= 0) return [];

		return XsUserForbiddenLog::query()
			->columns("FROM_UNIXTIME( dateline, '%Y-%m-%d' ) AS day, SUM(1) as num")
			->where("dateline >= :start: and dateline < :end:", ['start' => $start, 'end' => $end])
			->groupBy("day")
			->execute()
			->toArray();
	}
}