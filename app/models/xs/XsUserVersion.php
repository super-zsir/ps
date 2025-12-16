<?php

namespace Imee\Models\Xs;

class XsUserVersion extends BaseModel
{
	/**
	 * 根据uid批量获取版本信息
	 * @param array $uidArr uid
	 * @param array $fieldArr 查询的字段
	 * @return array
	 */
	public static function getUserVersionBatch($uidArr = [], $fieldArr = ['uid', 'version', 'platform'])
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
			'bind' => ['uid' => $uidArr],
		))->toArray();

		if (empty($data)) return [];

		return array_column($data, null, 'uid');
	}

	/**
	 * @param array $filter
	 * @param string $order
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function getList(array $filter, string $order = '', int $offset = -1, int $limit = -1): array
	{
		$model = self::model();
		$model->where('1=1');
		if (!empty($filter['mac'])) {
			$model->andWhere('mac = :mac:', ['mac' => $filter['mac']]);
		}
		if (isset($filter['uid']) && $filter['uid'] !== '') {
			$model->andWhere('uid = :uid:', ['uid' => (int)$filter['uid']]);
		}

		if ($order) {
			$model->order($order);
		}

		if ($offset != -1 && $limit != -1) {
			$model->limit($limit, $offset);
		}

		return $model->execute()->toArray();
	}

	private static function model(): \Phalcon\Mvc\Model\Criteria
	{
		return self::query();
	}

    public static function getValues($uids, $columns = '*')
    {
        if (!is_array($uids) || !$uids) {
            return [];
        }

        return self::find([
            'uid in ({uids:array})',
            'bind' => ['uids' => array_values($uids)],
            'columns' => $columns
        ])->toArray();
    }
}

