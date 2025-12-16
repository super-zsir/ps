<?php
namespace Imee\Models\Xss;

class CsmsFieldSceneMachine extends BaseModel
{
	const STATE_NORMAL = 1;
	const STATE_CANCEL = 2;
	const TYPE_NORMAL = 1; // 忽略其他检测结果

	const state = [
		1 => '正常',
		2 => '下线',
	];

	/**
	 * @param array $condition
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 */
	public static function queryBuilder(array $condition=[])
	{
		$query = static::baseQueryBuilder($condition);
		foreach ($condition as $key => $value) {
			switch ($key) {
				case 'id':
					$query->andWhere('id = :id:', ['id' => $value]);
					break;
				case 'state':
					$query->andWhere('state = :state:', ['state' => $value]);
					break;
				case 'field_scene_id':
					$query->andWhere('field_scene_id = :field_scene_id:', ['field_scene_id' => $value]);
					break;
				case 'groupBy':
					$query->groupBy($value);
					break;
				case 'orderBy':
					$query->orderBy($value);
					break;
				case 'limit':
					$query->limit($value);
					break;
				case 'offset':
					$query->offset($value);
					break;
				case 'having':
					$query->having($value);
					break;
				case 'columns':
					// 查询的字段
					$query->columns($value);
					break;
				default:
					break;
			}
		}
		return $query;
	}
}
