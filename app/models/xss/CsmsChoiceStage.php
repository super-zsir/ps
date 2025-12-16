<?php

namespace Imee\Models\Xss;

/**
 * 审核项外显方式配置表
 * Class CsmsChoiceStage
 * @package Imee\Models\Xss
 */
class CsmsChoiceStage extends BaseModel
{
    const STATE_NORMAL = 1;
    const state = [
        1 => '正常',
        2 => '下线'
    ];

    const stage = [
        'op' => '初审',
        'op2' => '复审',
        'op3' => '质检',
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
                case 'cid':
                    $query->andWhere('cid = :cid:', ['cid' => $value]);
                    break;
                case 'state':
                    $query->andWhere('state = :state:', ['state' => $value]);
                    break;
                case 'stage':
                    $query->andWhere('stage = :stage:', ['stage' => $value]);
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