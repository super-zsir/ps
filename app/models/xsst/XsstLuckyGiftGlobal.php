<?php

namespace Imee\Models\Xsst;

class XsstLuckyGiftGlobal extends BaseModel
{
	protected static $primaryKey = 'id';

	/**
	 * 数据写入
	 * @param array $data
	 * @return void
	 */
	public static function saveRows(array $data)
	{
		foreach ($data as $v) {
			$info = self::findOneByWhere([
				['exec_date', '=', $v['exec_date']],
				['bigarea_id', '=', $v['bigarea_id']]
			], true);
			if (empty($info)) {
				self::add($v);
			} else {
				self::edit($info['id'], $v);
			}
		}
	}

	/**
	 * 计算累计净收益
	 * @param $bigareaId
	 * @param $start
	 * @return mixed
	 */
	public static function sumNetProfit(int $bigareaId, int $start)
	{
		return self::sum([
			'column' => 'system_win_price',
			'conditions' => 'exec_date < :start: and bigarea_id = :bigarea_id:',
			'bind' => [
				'start' => $start,
				'bigarea_id' => $bigareaId,
			]
		]);
	}
}