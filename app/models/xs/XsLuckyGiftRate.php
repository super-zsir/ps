<?php

namespace Imee\Models\Xs;

class XsLuckyGiftRate extends BaseModel
{
	protected static $primaryKey = 'id';

	/**
	 * 获取权重总数
	 * @param array $condition
	 * @return float|int
	 */
	public static function getWeightSum(array $condition)
	{
		$list = self::getListByWhere($condition, 'weight');

		return array_sum(array_column($list, 'weight'));
	}
}