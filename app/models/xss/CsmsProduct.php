<?php

namespace Imee\Models\Xss;

class CsmsProduct extends BaseModel
{
    const STATE_NORMAL = 1;
    const state = [
        1 => '正常',
        2 => '下线',
    ];

    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition = [])
    {
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'state':
                    $query->andWhere('state = :state:', ['state' => $value]);
                    break;
                case 'app_id':
                    $query->andWhere('app_id = :app_id:', ['app_id' => $value]);
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
