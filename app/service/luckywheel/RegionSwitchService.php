<?php

namespace Imee\Service\Luckywheel;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsBigareaSwitch;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
	public function getList(array $params, $order, $page, $pageSize) : array
	{
		$res = XsBigarea::getListAndTotal([], 'id, name, lucky_wheel_config', $order, $page, $pageSize);
		$bigareaIds = array_column($res['data'], 'id');
		$logs = BmsBigareaSwitch::getLatestUpdateLog('wheel', $bigareaIds);
		foreach ($res['data'] as &$v) {
			$v['bigarea_id'] = (string) $v['id'];
			$v['switch'] = (string) (json_decode($v['lucky_wheel_config'], true)['switch'] ?? 0);
			$v['admin_name'] = $logs[$v['id']]['update_uname'] ?? '-';
			$v['dateline'] = $logs[$v['id']]['dateline'] ?? '-';
		}
		return $res;
	}

	public function modify(int $id, int $status)
	{
		$info = XsBigarea::findOne($id);
		if (empty($info)) {
			return [false, '当前大区不存在'];
		}
		$update = [
			'switch' => $status
		];
        list ($res, $msg) = XsBigarea::edit($id, [
            'lucky_wheel_config' => json_encode($update)
        ]);
		if ($res) {
			BmsBigareaSwitch::addLog('wheel', $update, $id, Helper::getSystemUid());
			return [true, ''];
		}
		return [false, $msg];
	}
}