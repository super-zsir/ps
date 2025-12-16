<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskUser;

use Imee\Models\Xs\BaseModel;
use Imee\Service\Domain\Context\Audit\RiskUser\RiskDataCountContext;
use Imee\Service\Helper;

class RiskDataCountProcess
{
	protected $context;

	public function __construct(RiskDataCountContext $context)
	{
		$this->context = $context;
	}

	public function handle()
	{
		$start = !empty($this->context->start) ? strtotime($this->context->start) : strtotime(date('Y-m-d', strtotime('today')));
		$end = !empty($this->context->end) ? strtotime($this->context->end) + 86400 : strtotime(date('Y-m-d', strtotime('today'))) + 86400;

		$sql = <<<SQL
			select FROM_UNIXTIME(create_time, '%Y-%m-%d') as day,
       		sum(if(status = 1, 1, 0)) as surplus_data,
       		sum(if(status = 2, 1, 0)) as pass_data,
       		sum(if(status = 3, 1, 0)) as reject_data
			from xs_user_reaudit
			where create_time >= {$start} AND create_time < {$end}
			group by day;
SQL;
		$data = Helper::fetch($sql, null, BaseModel::SCHEMA_READ);
		if (empty($data)) {
			return [
				'data' => [],
				'total' => 0,
			];
		}

		foreach ($data as &$v) {
			$v['new_data'] = $v['surplus_data'] + $v['pass_data'] + $v['reject_data'];
		}

		return [
			'data' => $data,
			'total' => count($data),
		];
	}
}