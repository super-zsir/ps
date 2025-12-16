<?php

namespace Imee\Models\Xss;

class CsmsVerifyKanbanDetail extends BaseModel
{
    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition=[])
    {
        $alias = '';
        if (isset($condition['alias'])) {
            $alias = "{$condition['alias']}.";
        }
        $query = static::baseQueryBuilder($condition);
        unset($condition['alias']);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'time_create_elg':
                    $query->andWhere($alias.'dateline >= :time_create_lg:', ['time_create_lg' => $value]);
                    break;
                case 'time_create_sg':
                    $query->andWhere($alias.'dateline < :time_create_sg:', ['time_create_sg' => $value]);
                    break;
                case 'dateline_start':
                    $query->andWhere($alias.'dateline >= :dateline_start:', ['dateline_start' => $value]);
                    break;
                case 'dateline_end':
                    $query->andWhere($alias.'dateline < :dateline_end:', ['dateline_end' => $value]);
                    break;
                case 'adminIds':
                    $query->inWhere($alias.'admin', $value);
                    break;
                case 'admin':
                    $query->andWhere($alias.'admin = :admin:', ['admin' => $value]);
                    break;
                case 'area':
                    is_array($value) ? $query->inWhere($alias.'area', $value) :
                        $query->andWhere($alias.'area = :area:', ['area' => $value]);
                    break;
                case 'auditItem':
                    is_array($value) ? $query->inWhere($alias.'audit_item', $value) :
                        $query->andWhere($alias.'audit_item = :auditItem:', ['auditItem' => $value]);
                    break;
                case 'actionItem':
                    $query->andWhere($alias.'action_item = :actionItem:', ['actionItem' => $value]);
                    break;
                case 'quartile_admin':
                    $query->leftJoin(CsmsVerifyKanbanQuartileAdmin::class, $alias.'admin = qa.admin and '.$alias.'audit_item = qa.audit_item and '.$alias.'dateline = qa.dateline and '.$alias.'verify_type in ("op","op2") ', 'qa');
                    break;
                case 'quartile_admin_op':
                    $query->leftJoin(CsmsVerifyKanbanQuartileAdmin::class, $alias.'admin = qa.admin and '.$alias.'audit_item = qa.audit_item and '.$alias.'dateline = qa.dateline and '.$alias.'verify_type = "op" ', 'qa');
                    break;
                case 'app_id':
                    $query->andWhere($alias.'app_id = :app_id:', ['app_id' => $value]);
                    break;
                case 'is_machine':
                    $query->andWhere($alias.'is_machine = :is_machine:', ['is_machine' => $value]);
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
                    is_array($value) ? $query->inWhere($alias.$key, $value) :
                        $query->andWhere($alias."{$key} = :{$key}:", ["{$key}" => $value]);
                    break;
            }
        }
        return $query;
    }

    /**
     * 阶段
     * @param string|null $name
     * @return string|string[]
     */
    public static function verifyTypeFilter(?string $name)
    {
        switch ($name) {
            case 'type_one':
                // 初审环节 初审响应
                return 'op';
            case 'type_two':
                // 复审环节 复审响应+复审不响应
                return ['op2_machine', 'op2'];
            case 'type_three':
                // 人工初审 初审响应+复审响应
                return ['op', 'op2'];
            case 'type_four':
                // 人工复审 复审不响应
                return ['op2_machine'];
            case 'type_five':
                // 人工质检 质检
                return ['op3_machine'];
            default:
                return '';
        }
    }
}