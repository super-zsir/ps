<?php

namespace Imee\Models\Xsst;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;
use Imee\Service\Helper;

class BmsBigareaSwitch extends BaseModel
{
	use MysqlCollectionTrait;

    private static $logType = [
        'luck' => 1,
        'dice' => 2,
        'slot' => 3,
        'red_packet' => 4,
        'wheel' => 5,
    ];

	public static function addLog($type, $data, $sid, $uid)
	{
		$data = [
			'type' => self::$logType[$type],
			'sid' => $sid,
			'content' => json_encode($data),
			'update_uid' => $uid,
			'dateline' => time()
		];
		return self::add($data);
	}

	public static function getLatestUpdateLog($type, $ids)
	{
		$type = self::$logType[$type] ?? 0;
		if ($type == 0 || empty($ids)) {
			return [];
		}
		$ids = implode(',', $ids);
		$sql = "SELECT sid,update_uid,update_uname,dateline FROM bms_bigarea_switch WHERE id IN (
    				SELECT MAX(id) FROM bms_bigarea_switch WHERE `type`={$type} AND sid IN({$ids}) GROUP BY sid
    			)";
		$logs = Helper::fetch($sql, null, BaseModel::SCHEMA);
		if (empty($logs)) return [];
		foreach ($logs as &$log) {
			if (empty($log['update_uname'])) {
				$cmsUser = CmsUser::findOne($log['update_uid']);
				$log['update_uname'] = $cmsUser['user_name'] ?? '';
			}
			$log['dateline'] = date('Y-m-d H:i', $log['dateline']);
		}
		return array_column($logs, null, 'sid');
	}
}