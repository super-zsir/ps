<?php

namespace Imee\Models\Xsst;

class XsstReportExamLog extends BaseModel
{
    const state = [
        0 => '待处理',
        1 => '处理中',
        2 => '已处理',
        3 => '已驳回'
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
                case 'report_id':
                    $query->andWhere('report_id = :report_id:', ['report_id' => $value]);
                    break;
                case 'report_id_array':
                    $query->inWhere('report_id', $value);
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
