<?php

namespace Imee\Comp\Common\Orm\Traits;

/**
 * Trait MongoCollectionTrait
 * 用于处理MongoDB集合操作的trait
 */
trait MongoCollectionTrait
{
    /**
     * 获取条数
     * @param null $parameters
     * @return int
     */
    public static function aggCount($parameters = null)
    {
        // 若未传入任何参数，则直接返回集合中的文档数量
        if (empty($parameters)) {
            return static::count();
        }
        $aggregate = static::parseParameters($parameters);

        if (!isset($parameters['count'])) {
            $aggregate['count'] = [
                '$count' => 'count'
            ];
        }

        $agg = static::aggregate(array_values($aggregate));
        $agg = current(static::formatList($agg));

        return (int) ($agg['count'] ?? 0);
    }

    public static function aggFind($parameters = null)
    {
        $aggregate = static::parseParameters($parameters);

        $agg = static::aggregate(array_values($aggregate));

        return static::formatList($agg);
    }

    public static function aggFindFirst($parameters = null)
    {
        $parameters['limit'] = 1;

        $aggregate = static::parseParameters($parameters);

        $agg = static::aggregate(array_values($aggregate));

        return current(static::formatList($agg));
    }

    public static function parseParameters($conditions = null)
    {
        $aggregate = $fields = $join = $unwind = $order = $offset = $limit = [];

        if (empty($conditions)) {
            $conditions['offset'] = 0;
        }

        foreach (['fields', 'join', 'unwind', 'order', 'offset', 'limit'] as $key) {
            if (isset($conditions[$key])) {
                ${$key} = array_merge($aggregate, static::{"parse" . ucfirst($key)}($conditions[$key]));
                unset($conditions[$key]);
            }
        }

        // TODO: 实现group by功能
        unset($conditions['group']);

        $where = $conditions['conditions'] ?? $conditions;

        if (!empty($where)) {
            // 不能调整顺序!!!
            $aggregate = array_merge($fields, $join, $unwind, static::parseConditions($where, $aggregate), $order, $offset, $limit);
        } else {
            $aggregate = array_merge($fields, $join, $unwind, $order, $offset, $limit);
        }

        return $aggregate;
    }

    public static function parseJoin($joins)
    {
        $aggregate = [];

        foreach ($joins as $key => $condition) {
            [$table, $fields, $fieldAs] = $condition;
            [$leftPk, $rightPk] = $fields;

            $aggregate[$table] = [
                '$lookup' => [
                    'from'         => $table,
                    'localField'   => $leftPk,
                    'foreignField' => $rightPk,
                    'as'           => $fieldAs
                ]
            ];
        }

        return $aggregate;
    }

    public static function parseUnwind($unwinds)
    {
        $aggregate = [];

        foreach ($unwinds as $unwind) {
            $aggregate['unwind_' . $unwind] = ['$unwind' => ['path' => '$' . $unwind, 'preserveNullAndEmptyArrays' => true]];
        }

        return $aggregate;
    }

    public static function parseLimit($limit)
    {
        return ['limit' => ['$limit' => (int) $limit]];
    }

    public static function parseOffset($offset)
    {
        return ['offset' => ['$skip' => (int) $offset]];
    }

    public static function parseOrder($order)
    {
        $aggOrder = [];
        $orderMap = ['asc' => 1, 'desc' => -1];

        foreach ($order as $field => $desc) {
            $aggOrder[$field] = $orderMap[strtolower($desc)];
        }

        return ['order' => ['$sort' => $aggOrder]];
    }

    public static function parseConditions($condition, $aggregate)
    {
        $master = $join = [];

        foreach ($condition as $field => $item) {
            if (!is_array($item)) {
                if (false === strpos($field, '.')) {
                    $master[$field] = $item;
                } else {
                    $join[$field] = $item;
                }
                continue;
            }

            // 处理模糊搜索
            if (isset($item['like'])) {
                $regex = ['$regex' => $item['like']];
                if (false === strpos($field, '.')) {
                    $master[$field] = $regex;
                } else {
                    $join[$field] = $regex;
                }
            }

            if (false === strpos($field, '.')) {
                $master[$field] = $item;
            } else {
                $join[$field] = $item;
            }
        }

        if (!empty($master)) {
            $aggregate = array_merge(['master_conditions' => ['$match' => $master]], $aggregate);
        }

        if (!empty($join)) {
            $aggregate = array_merge($aggregate, ['join_conditions' => ['$match' => $join]]);
        }

        return $aggregate;
    }

    public static function parseFields($fields)
    {
        return ['fields' => ['$project' => $fields]];
    }

    /**
     * 格式化数据
     * @param $items
     * @return array
     */
    protected static function formatList($items)
    {
        if (empty($items)) {
            return [];
        }

        $list = [];
        foreach ($items as $item) {
            $list[] = json_decode(json_encode($item), true);
        }

        return $list;
    }
}
