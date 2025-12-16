<?php

namespace Imee\Models\Xss;

class CsmsChoiceField extends BaseModel
{
    const STATE_NORMAL = 1;
    const STATE_OFF = 2;
    const state = [
        1 => '正常',
        2 => '下线'
    ];

    const ignore_write = [
        0 => '不忽略',
        1 => '忽略'
    ];

    const ignore_update = [
        0 => '不忽略',
        1 => '忽略'
    ];

    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition = [])
    {
        $alias = '';
        if (isset($condition['alias'])) {
            $alias = $condition['alias'] . '.';
        }
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'cid':
                    $query->andWhere($alias . 'cid = :cid:', ['cid' => $value]);
                    break;
                case 'field':
                    $query->andWhere($alias . 'field = :field:', ['field' => $value]);
                    break;
                case 'choice':
                    $query->andWhere($alias . 'choice = :choice:', ['choice' => $value]);
                    break;
                case 'state':
                    $query->andWhere($alias . 'state = :state:', ['state' => $value]);
                    break;
                case 'id_array':
                    $query->inWhere($alias . 'id', $value);
                    break;
                case 'type':
                    $query->andWhere($alias . 'type = :type:', ['type' => $value]);
                    break;
                case 'leftChoice':
                    $query->leftJoin(CsmsChoice::class, "cc.id = {$alias}cid", 'cc');
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
