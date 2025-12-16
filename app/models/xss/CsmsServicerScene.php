<?php
namespace Imee\Models\Xss;

class CsmsServicerScene extends BaseModel
{
    const STATE_NORMAL = 1;
    const STATE_CANCEL = 2;

    const state = [
        1 => '正常',
        2 => '下线'
    ];

    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition=[])
    {
        $alias = '';
        if (isset($condition['alias'])) {
            $alias = $condition['alias'].'.';
        }
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'leftjoin_csms_servicer':
                    $query->leftJoin(CsmsServicer::class, 'cs.id = '.$alias.'sid', 'cs');
                    break;
                case 'sid':
                    $query->andWhere($alias.'sid = :sid:', ['sid' => $value]);
                    break;
                case 'id_array':
                    $query->inWhere($alias.'id', $value);
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
