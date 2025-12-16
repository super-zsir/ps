<?php

namespace Imee\Models\Xss;

class CsmsKanbanQuartile extends BaseModel
{
    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition=[])
    {
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'day':
                    $query->andWhere('dateline = :day:', ['day' => $value]);
                    break;
                case 'dateline_start':
                    $query->andWhere('dateline >= :dateline_start:', ['dateline_start' => $value]);
                    break;
                case 'dateline_end':
                    $query->andWhere('dateline < :dateline_end:', ['dateline_end' => $value]);
                    break;
                case 'audit_item':
                    is_array($value) ? $query->inWhere('audit_item', $value) :
                        $query->andWhere('audit_item = :auditItem:', ['auditItem' => $value]);
                    break;
                case 'verify_type':
                    is_array($value) ? $query->inWhere('verify_type', $value) :
                        $query->andWhere('verify_type = :verifyType:', ['verifyType' => $value]);
                    break;
                case 'spend_time_ls':
                    $query->andWhere('spend_time < :spend_time:', ['spend_time' => $value]);
                    break;
                case 'app_id':
                    $query->andWhere('app_id = :app_id:', ['app_id' => $value]);
                    break;
                case 'admin':
                    $query->andWhere('admin = :admin:', ['admin' => $value]);
                    break;
                case 'id_lg':
                    $query->andWhere('id > :id_lg:', ['id_lg' => $value]);
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
                    is_array($value) ? $query->inWhere($key, $value) :
                        $query->andWhere("{$key} = :{$key}:", ["{$key}" => $value]);
                    break;
            }
        }
        return $query;
    }
}