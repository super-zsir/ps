<?php

namespace Imee\Models\Xss;

class XsChatMessageNew extends BaseModel
{
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
        foreach ($condition as $key => $value ) {
            switch ($key) {
                case 'sid':
                    $query->andWhere($alias.'sid = :sid:', ['sid' => $value]);
                    break;
                case 'from_user_id':
                    $query->andWhere($alias.'from_user_id = :from_user_id:', ['from_user_id' => $value]);
                    break;
                case 'id_array':
                    $query->inWhere($alias.'id', $value);
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
}