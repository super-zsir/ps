<?php
namespace Imee\Models\Xs;

class XsUserPlatform extends BaseModel
{
	public static function getListByUid($uid, $columns = '*'): array
	{
		if (!$uid) {
			return [];
		}

		if (!is_array($uid)) {
			$uid = [$uid];
		} else {
			$uid = array_values($uid);
		}

		return self::find([
			'conditions' => "uid in ({uid:array})",
			'bind' => ['uid' => $uid],
			'columns' => $columns
		])->toArray();
	}
}