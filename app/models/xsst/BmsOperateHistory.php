<?php

namespace Imee\Models\Xsst;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Service\Helper;

class BmsOperateHistory extends BaseModel
{
    const PRICE_LEVEL = 'user_price_level';

	const BANNER_SETTING = 'bbc_banner_settings';
	const XS_LUCK_GIFT_DIVIDED = 'xs_luck_gift_divided';
	const XS_LUCK_GIFT_RATE = 'xs_luck_gift_rate';
	const XS_LUCK_GIFT_RATE_ADJUSTMENT = 'xs_luck_gift_rate_adjustment';
	const XS_MEDAL_RESOURCE = 'xs_medal_resource';
	const XS_USER_MEDAL = 'xs_user_medal';
	const PRETTY_NUM = 'user_pretty_num';
	public static $source = [
		self::BANNER_SETTING => 1,
		'bms_user_code_reset' => 2,
		'chat_interact_suggestion' => 3,
		'bbc_campaign_user_label' => 4,
		'quick_say_hi_template' => 5,
		'quick_say_hi_template_rule' => 6,
		'bbc_gift_skin' => 7,
		self::XS_LUCK_GIFT_DIVIDED => 8,
		self::XS_LUCK_GIFT_RATE => 9,
		self::XS_LUCK_GIFT_RATE_ADJUSTMENT => 10,
		self::PRETTY_NUM => 11,
        self::XS_MEDAL_RESOURCE => 13,
        self::XS_USER_MEDAL => 14,
        self::PRICE_LEVEL => 15,
	];

	public static function insertRows(string $table, int $sid, array $data, array $changeFields) : bool
	{
		if (!isset(self::$source[$table])) return false;
		$uid = intval($data['admin_uid'] ?? 0);
		if (empty($changeFields)) return false;

		array_map(function($field) use (&$data) {
			if (!isset($data[$field])) unset($data[$field]);
		}, $changeFields);

		if (empty($data)) return false;

		$m = new self();
		$m->source = self::$source[$table];
		$m->sid = $sid;
		$m->content = json_encode($data);
		$m->update_uid = $uid;
		$m->dateline = time();
		return !!($m->save());
	}

	public static function insertLog(string $table, int $sid, array $data, int $uid) : bool
	{
		if (!isset(self::$source[$table])) return false;
		$m = new self();
		$m->source = self::$source[$table];
		$m->sid = $sid;
		$m->content = json_encode($data);
		$m->update_uid = $uid;
		$m->dateline = time();
		return !!($m->save());
	}

	public static function getLatestUpdateLog(string $table, array $ids) : array
	{
		$source = self::$source[$table] ?? 0;
		if ($source == 0 || empty($ids)) return [];
		$ids = implode(',', $ids);
		$sql = "SELECT sid,update_uid,update_uname,dateline FROM bms_operate_history WHERE id IN (
    				SELECT MAX(id) FROM bms_operate_history WHERE `source`={$source} AND sid IN({$ids}) GROUP BY sid
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

	public static function getHistoryBySid(string $table, int $sid, array $query) : array
	{
		$source = self::$source[$table] ?? 0;
		if ($source == 0 || $sid < 1) return [];
		$conditions = [
			'conditions' => 'source=:source: AND sid=:sid:',
			'bind' => compact('source', 'sid')
		];
		$total = self::count($conditions);
		if ($total == 0) return [];
		$data = self::find($conditions + $query)->toArray();
		if (!empty($data)) {
			foreach ($data as &$v) {
				if (empty($v['update_uname'])) {
					$cmsUser = CmsUser::findOne($v['update_uid']);
					$v['update_uname'] = $cmsUser['user_name'];
				}
				$v['dateline'] = Helper::now($v['dateline']);
			}
		}
		return compact('data', 'total');
	}
}