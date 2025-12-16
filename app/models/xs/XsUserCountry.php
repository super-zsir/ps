<?php

namespace Imee\Models\Xs;

class XsUserCountry extends BaseModel
{
    protected static $primaryKey = 'uid';

	/**
	 * 根据uid批量获取城市信息
	 * @param array $uidArr uid
	 * @param array $fieldArr 查询的字段
	 * @return array
	 */
	public static function getUserCountryBatch($uidArr = [], $fieldArr = ['uid', 'country'])
	{
		if (empty($uidArr)) {
			return [];
		}
		if (!in_array('uid', $fieldArr)) {
			$fieldArr[] = 'uid';
		}

		$data = self::find(array(
			'columns' => implode(',', $fieldArr),
			'conditions' => "uid in ({uid:array})",
			'bind' => array(
				'uid' => $uidArr,
			),
		))->toArray();
		if (empty($data)) {
			return array();
		}

		return array_column($data, null, 'uid');
	}

	public static function addUserCountry(int $uid)
	{
		if ($uid < 1) return false;
		$rec = self::findFirst([
			'conditions' => 'uid=:uid:',
			'bind' => compact('uid')
		]);
		if ($rec) return false;
		$rec = new self();
		$rec->uid = $uid;
		$rec->country = '新加坡';
		$rec->latest_country = '新加坡';
		$rec->latest_country_code = 'SG';
		$rec->dateline = time();
		if ($rec->save()) return true;
		return false;
	}
}
