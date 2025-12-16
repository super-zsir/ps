<?php

namespace Imee\Service\Slot;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsBigareaSwitch;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
	public function getList(array $params, $order, $page, $pageSize) : array
	{
		$res = XsBigarea::getListAndTotal([], 'id, name, slot_config', $order, $page, $pageSize);
		$bigareaIds = array_column($res['data'], 'id');
		$logs = BmsBigareaSwitch::getLatestUpdateLog('slot', $bigareaIds);
		foreach ($res['data'] as &$v) {
			$v['bigarea_id'] = (string) $v['id'];
            $config = @json_decode($v['slot_config'], true);
			$v['switch'] = (string) ($config['switch'] ?? 0);
			$v['global_rank_switch'] = (string) ($config['global_rank_switch'] ?? 0);
			$v['admin_name'] = $logs[$v['id']]['update_uname'] ?? '-';
			$v['dateline'] = $logs[$v['id']]['dateline'] ?? '-';
		}
		return $res;
	}

	public function modify(int $id, int $status, int $globalSwitch): array
	{
		$info = XsBigarea::findOne($id);
		if (empty($info)) {
			return [false, '当前大区不存在'];
		}
		$update = [
			'big_area_id' => $id,
			'switch' => $status,
            'global_rank_switch' => $globalSwitch
		];
		list($res, $msg) = (new PsService())->setSlotSwitch($update);
		if ($res) {
			BmsBigareaSwitch::addLog('slot', $update, $id, Helper::getSystemUid());
			return [true, ''];
		}
		return [false, $msg];
	}
}