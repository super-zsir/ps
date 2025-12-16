<?php

namespace Imee\Models\Xs;

class XsLuckyGiftDivided extends BaseModel
{
	protected static $primaryKey = 'id';

	/**
	 * 批量获取礼物比例
	 * @param array $giftIds
	 * @return array
	 */
	public static function getGiftProByIdsBatch(array $giftIds) : array
	{
		if (empty($giftIds)) {
			return [];
		}
		$data = self::getListByWhere([
			['gift_id', 'in', $giftIds],
			['is_delete', '=', 0]
		], 'gift_id, proportion');

		if ($data) {
			$data = array_column($data, 'proportion', 'gift_id');
		}

		return $data;
	}
}