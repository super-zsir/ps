<?php

namespace Imee\Models\Xss;


class CsmsAudit extends BaseModel
{


	const DELETED_INIT = 3;
	const DELETED_PASS = 1;
	const DELETED_REFUSE = 2;
	const DELETED_DELETE = 4;
	const DELETED_CALLBACK = 5;

	const DELETED2_INIT = 3;
	const DELETED2_PASS = 1;
	const DELETED2_REFUSE = 2;
    const DELETED2_DELETE = 4;

	const DELETED3_INIT = 3;
	const DELETED3_PASS = 1;
	const DELETED3_REFUSE = 2;


	const state = [
		self::DELETED_INIT => '待审',
		self::DELETED_PASS => '已通过',
		self::DELETED_REFUSE => '已清空',
		self::DELETED_DELETE => '已删除',
		self::DELETED_CALLBACK => '已撤回',
	];



	const delexted = [
		self::DELETED_INIT => '待审',
		self::DELETED_PASS => '已通过',
		self::DELETED_REFUSE => '已清空',
	];

	const deleted2 = [
		self::DELETED2_INIT => '待审',
		self::DELETED2_PASS => '已通过',
		self::DELETED2_REFUSE => '已清空',
	];

	const deleted3 = [
		self::DELETED3_INIT => '待审',
		self::DELETED3_PASS => '已通过',
		self::DELETED3_REFUSE => '已清空',
	];




	const MACHINE_UNKNOWN = 0;
	const MACHINE_PASS = 1;
	const MACHINE_REFUSE = 2;
	const MACHINE_IDENTIFY = 3;
	const MACHINE_DANGER = 4;

	public static $machine_state = [
		self::MACHINE_UNKNOWN => '未识别',
		self::MACHINE_PASS => '通过',
		self::MACHINE_REFUSE => '拒绝',
		self::MACHINE_IDENTIFY => '识别中',
		self::MACHINE_DANGER => '严重违规',
	];




	public static function findFirstValue($id)
	{
		$rec =  self::findFirst(
			array(
				"id = :id:",
				"bind" => array("id" => $id)
			)
		);
		return $rec ? $rec->toArray() : array();
	}

    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition=[])
    {
        $alias = '';
        if (isset($condition['alias'])) {
            $alias = $condition['alias'] . '.';
        }
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'id':
                    $query->andWhere($alias.'id = :pk:', ['pk' => $value]);
                    break;
                case 'choice':
                    $query->andWhere($alias.'choice = :choice:', ['choice' => $value]);
                    break;
                case 'id_lg':
                    $query->andWhere($alias.'id > :id:', ['id' => $value]);
                    break;
                case 'dateline_start':
                    $query->andWhere($alias.'dateline >= :dateline_start:', ['dateline_start' => $value]);
                    break;
                case 'dateline_end':
                    $query->andWhere($alias.'dateline < :dateline_end:', ['dateline_end' => $value]);
                    break;
                case 'taskid':
                    $query->andWhere($alias.'taskid = :taskid:', ['taskid' => $value]);
                    break;
                case 'op_lg':
                    $query->andWhere($alias.'op > :op:', ['op' => $value]);
                    break;
                case 'op2':
                    $query->andWhere($alias.'op2 = :second:', ['second' => $value]);
                    break;
                case 'columns':
                    // 查询的字段
                    $query->columns($value);
                    break;
                case 'orderBy':
                    $query->orderBy($value);
                    break;
                case 'groupBy':
                    $query->groupBy($value);
                    break;
                case 'limit':
                    $query->limit($value);
                    break;
                case 'offset':
                    $query->offset($value);
                    break;
                default:
                    break;
            }
        }
        return $query;
    }

    protected $allowEmptyStringArr = [
        'origin',
        'value'
    ];
}