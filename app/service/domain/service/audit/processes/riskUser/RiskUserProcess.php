<?php
namespace Imee\Service\Domain\Service\Audit\Processes\RiskUser;

use Imee\Models\Xs\BaseModel as XsBaseModel;
use Imee\Models\Xsst\BaseModel as XsstBaseModel;
use Imee\Service\Helper;

class RiskUserProcess
{
	public function handleStatistics($start, $end, $rule_type)
	{
		$start = intval($start);
		$end = intval($end);
		$add_sql = $this->handleRuleTypeSql($rule_type);

		if ($start <= 0 || $end <= 0) return [];

		$sql = <<<SQL
				SELECT FROM_UNIXTIME( create_time, '%Y-%m-%d' ) AS c_time, `type` as rule_type,
				SUM(1) AS total,
				SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) as unhandle, 
				SUM(CASE WHEN `status` > 1 THEN 1 ELSE 0 END) as handled,
				SUM(CASE WHEN `status` = 3 THEN 1 ELSE 0 END) as correct_catch
				FROM xs_user_reaudit 
				WHERE {$add_sql}
				AND create_time >= {$start} AND create_time < {$end}
				GROUP BY c_time, rule_type;
SQL;
		$res = Helper::fetch($sql, null, XsBaseModel::SCHEMA_READ);
		if (empty($res)) return [];
		$rule_types = array_unique(array_column($res, 'rule_type'));

		$rule_types_str = "(" . implode(",", $rule_types) . ")";
		$sql_forbidden = <<<SQL
				select FROM_UNIXTIME( create_time, '%Y-%m-%d' ) AS c_time, rule_type,
				SUM(CASE WHEN `op_type` = 1 THEN 1 ELSE 0 END) as forbidden,
				SUM(CASE WHEN `duration` = 315360000 AND op_type = 1 THEN 1 ELSE 0 END) as forbidden_forever,
				SUM(CASE WHEN `duration` <> 315360000 AND op_type = 1 THEN 1 ELSE 0 END) as forbidden_normal,
				SUM(CASE WHEN `role` > 1 THEN 1 ELSE 0 END) as forbidden_god,
				SUM(CASE WHEN `new_user` = 1 THEN 1 ELSE 0 END) as forbidden_fresh
				from xsst_forbidden_risk_log 
				where 1
				and create_time >= {$start} and create_time < {$end}
				and rule_type in {$rule_types_str}
				GROUP BY c_time, rule_type;
SQL;

		$res_forbidden = Helper::fetch($sql_forbidden, null, XsstBaseModel::SCHEMA);
		$forbidden_info = [];
		if ($res_forbidden) {
			foreach ($res_forbidden as $val) {
				$forbidden_info[$val['c_time']][$val['rule_type']]['forbidden'] = $val['forbidden'];
				$forbidden_info[$val['c_time']][$val['rule_type']]['forbidden_forever'] = $val['forbidden_forever'];
				$forbidden_info[$val['c_time']][$val['rule_type']]['forbidden_normal'] = $val['forbidden_normal'];
				$forbidden_info[$val['c_time']][$val['rule_type']]['forbidden_god'] = $val['forbidden_god'];
				$forbidden_info[$val['c_time']][$val['rule_type']]['forbidden_fresh'] = $val['forbidden_fresh'];
			}
		}
		unset($res_forbidden);

		$result = [];
		foreach ($res as $val) {
			$day = $val['c_time'];
			$key = $val['rule_type'];
			$result[$day][$key]['total'] = $val['total'];
			$result[$day][$key]['unhandle'] = $val['unhandle'];
			$result[$day][$key]['handled'] = $val['handled'];
			$result[$day][$key]['correct_catch'] = $val['correct_catch'];
			$result[$day][$key]['forbidden'] = isset($forbidden_info[$day]) && isset($forbidden_info[$day][$val['rule_type']]) ? $forbidden_info[$day][$val['rule_type']]['forbidden'] : 0;
			$result[$day][$key]['forbidden_forever'] = isset($forbidden_info[$day]) && isset($forbidden_info[$day][$val['rule_type']]) ? $forbidden_info[$day][$val['rule_type']]['forbidden_forever'] : 0;
			$result[$day][$key]['forbidden_normal'] = isset($forbidden_info[$day]) && isset($forbidden_info[$day][$val['rule_type']]) ? $forbidden_info[$day][$val['rule_type']]['forbidden_normal'] : 0;
			$result[$day][$key]['forbidden_god'] = isset($forbidden_info[$day]) && isset($forbidden_info[$day][$val['rule_type']]) ? $forbidden_info[$day][$val['rule_type']]['forbidden_god'] : 0;
			$result[$day][$key]['forbidden_fresh'] = isset($forbidden_info[$day]) && isset($forbidden_info[$day][$val['rule_type']]) ? $forbidden_info[$day][$val['rule_type']]['forbidden_fresh'] : 0;
		}

		return $result;
	}

	private function handleRuleTypeSql($rule_type)
	{
		if (empty($rule_type)) return "1 = 1";
		$temp = [];
		foreach ($rule_type as $type) {
			if ($type != '') $temp[] = $type;
		}
		return $temp ? "`type` IN (" . implode(",", $temp) . ")" : "1 = 1";
	}
}