<?php

namespace Imee\Service\Operate\User\Pay;

/**
 * 用户账户变化历史【金币】类型
 */
class GameCoinService extends PayHistoryAbstractService
{
	public function getPayHistoryList(): array
	{
		return ['data' => [], 'total' => 0]; // 现在没查询了
		/*$columns = ['columns' => 'id, uid, dateline, room_id rid, amount money, action_type, ext_info reason, owner_uid'];
		$this->conditions .= ' and amount_type= ' . \Pscurrencylog::AMOUNT_CURRENCY;
		$total = intval(\Pscurrencylog::count(['conditions' => $this->conditions]));
		if ($total === 0) {
			return array('data' => [], 'total' => 0);
		}
		$data = \Pscurrencylog::find(array_merge(['conditions' => $this->conditions], $this->query, $columns))->toArray();
		foreach ($data as &$val) {
			$val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
			$val['reason'] = !empty($val['reason']) ? (@unserialize($val['reason']) ?: @json_decode($val['reason'], true)) : '';
			$val['reason_person'] = !empty($val['reason']) ? highlight_string(var_export($val['reason'], true), true) : '';
			$val['reason_display'] = $val['action_type'];
			$val['extra'] = '';
			$val['to'] = $val['owner_uid'];
			$val['op'] = '';
		}
		return ['data' => $data, 'total' => $total];*/
	}
}