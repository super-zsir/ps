<?php

namespace Imee\Service\Luckygift;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsBigareaSwitch;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
	public function getList(array $params, int $page, int $pageSize): array
	{
		$result = XsBigarea::getBigareaLuckyPlaySwitchList([], $page, $pageSize);
		$bigareaIds = array_column($result['data'], 'id');
		$logs = BmsBigareaSwitch::getLatestUpdateLog('luck', $bigareaIds);
		foreach ($result['data'] as &$v) {
			$v['lucky_gift_switch'] = (string) $v['lucky_gift_switch'];
			$v['admin_name'] = $logs[$v['id']]['update_uname'] ?? '-';
			$v['dateline'] = $logs[$v['id']]['dateline'] ?? '-';
		}
		return $result;
	}

	public function modify(int $id, int $status)
	{
        $info = XsBigarea::findOne($id);
        if (empty($info)) {
            return [false, '当前大区不存在'];
        }
        $update = [
            'big_area_id' => $id,
            'switch' => $status
        ];
        list($res, $msg) = (new PsService())->luckyGiftSwitch($update);
        if ($res) {
            BmsBigareaSwitch::addLog('luck', $update, $id, Helper::getSystemUid());
            return [true, ''];
        }
        return [false, $msg];
	}
}