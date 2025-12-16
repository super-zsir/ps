<?php

namespace Imee\Models\Xss;

class XssCircleVideo extends BaseModel
{
    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition=[])
    {
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value ) {
            switch ($key) {
                case 'topic_array':
                    $query->inWhere('topic_id', $value);
                    break;
                case 'dateline_start':
                    $query->andWhere('create_time >= :start:', ['start' => $value]);
                    break;
                case 'dateline_end':
                    $query->andWhere('create_time < :end:', ['end' => $value]);
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