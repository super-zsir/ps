<?php

namespace Imee\Models\Xss;

class XssReport extends BaseModel
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
                case 'rid_lg':
                    $query->andWhere($alias.'rid > 0');
                    break;
                case 'rid':
                    $query->andWhere($alias.'rid = :rid:', ['rid' => $value]);
                    break;
                case 'rid_array':
                    $query->inWhere($alias.'rid', $value);
                    break;
                case 'to':
                    $query->andWhere($alias.'to = :to:', ['to' => $value]);
                    break;
                case 'to_array':
                    $query->inWhere($alias.'to', $value);
                    break;
                case 'uid':
                    $query->andWhere($alias.'uid = :uid:', ['uid' => $value]);
                    break;
                case 'uid_array':
                    $query->inWhere($alias.'uid', $value);
                    break;
                case 'state':
                    $query->andWhere($alias.'state = :state:', ['state' => $value]);
                    break;
                case 'type':
                    $query->andWhere($alias.'type = :type:', ['type' => $value]);
                    break;
                case 'language':
                    $query->andWhere($alias.'language = :language:', ['language' => $value]);
                    break;
                case 'app_id':
                    $query->andWhere($alias.'app_id = :app_id:', ['app_id' => $value]);
                    break;
                case 'big_area':
                    $query->andWhere($alias.'big_area = :big_area:', ['big_area' => $value]);
                    break;
                case 'dateline_start':
                    $query->andWhere($alias.'dateline >= :start:', ['start' => $value]);
                    break;
                case 'dateline_end':
                    $query->andWhere($alias.'dateline < :end:', ['end' => $value]);
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