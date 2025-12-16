<?php

namespace Imee\Comp\Common\Orm\Traits;

use Phalcon\Di;

trait ModelManagerTrait
{
    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function baseQueryBuilder(array $condition = [])
    {
        $modelsManager = Di::getDefault()->getShared('modelsManager');
        return $modelsManager->createBuilder()->addfrom(static::class, $condition['alias'] ?? null);
    }

    /**
     * 查询条件 若需要定义更多条件，请在自己对应表的model中重写当前方法
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition = [])
    {
        $alias = '';
        if (isset($condition['alias'])) {
            $alias = $condition['alias'] . '.';
        }
        $query = self::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'id_lg':
                    $query->andWhere($alias . 'id > :id:', ['id' => $value]);
                    break;
                /**
                 * left join方法实例
                 */
//                case 'leftJoinUser':
//                    $query->leftJoin(\User::class, $alias.'uid = u.id', 'u');
//                    break;
                case 'columns':
                    // 查询的字段
                    $query->columns($value);
                    break;
                case 'groupBy':
                    $query->groupBy($value);
                    break;
                case 'having':
                    $query->having($value);
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
                default:
                    is_array($value) ? $query->inWhere($alias . $key, $value) :
                        $query->andWhere($alias . "{$key} = :{$key}:", ["{$key}" => $value]);
                    break;
            }
        }
        return $query;
    }

    /**
     * @param array $condition
     * @return array
     */
    public static function handleList(array $condition): array
    {
        $query = static::queryBuilder($condition);
        return $query->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * @param array $condition
     * @return mixed
     */
    public static function handleOne(array $condition)
    {
        $query = static::queryBuilder($condition);
        return $query->getQuery()
            ->execute()
            ->getFirst();
    }

    /**
     * 查找数量
     * @param array $condition
     * @return int
     */
    public static function handleTotal(array $condition): int
    {
        if (!isset($condition['groupBy'])) {
            $condition = array_merge($condition, ['columns' => 'count(1) as count']);
            $query = static::queryBuilder($condition);
            $data = $query->getQuery()
                ->execute()
                ->getFirst();
            return !empty($data) ? ($data->count ?? 0) : 0;
        } else {
            $query = static::queryBuilder($condition);
            return $query->getQuery()
                ->execute()
                ->count();
        }
    }

    /**
     * @param array $condition
     * @return int
     */
    public static function handleTotalCount(array $condition): int
    {
        $query = static::queryBuilder($condition);
        return $query->getQuery()
            ->execute()
            ->count();
    }

    /**
     * @param int $id
     * @param array $condition
     * @return bool
     */
    public static function handleEdit(int $id, array $condition): bool
    {
        $model = static::findFirst("id = {$id}");
        if ($model) {
            foreach ($condition as $key => $item) {
                if ($model->{$key} != $item) {
                    $model->{$key} = $item;
                }
            }
            return $model->save();
        }
        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function handleDelete(int $id): bool
    {
        $model = static::findFirst("id = {$id}");
        if ($model) {
            return $model->delete();
        }
        return false;
    }

    /**
     * @param $data
     * @return false
     */
    public static function handleCreate($data): bool
    {
        $model = new self();

        foreach ($data as $k => $v) {
            $model->$k = $v;
        }

        return $model->save();
    }

    /**
     * 批量插入
     * @param array $rows
     * @return false|Model\QueryInterface
     */
    public static function handleBatchInsert(array $rows = [], $duplicat_update = '')
    {
        if (count($rows) == 0) {
            return false;
        }
        $fields = implode(',', array_keys($rows[0]));
        $values = [];
        foreach ($rows as $row) {
            $items = array_map(function ($item) {
                return sprintf("'%s'", htmlspecialchars($item, ENT_QUOTES));
            }, $row);
            $values[] = sprintf('(%s)', implode(',', $items));
        }
        $values = implode(',', $values);
        $class = new static();
        $table = $class->getSource();//uncamelize(lcfirst(get_called_class()));
        $sql = sprintf("INSERT INTO %s (%s) VALUES %s", $table, $fields, $values);
        if ($duplicat_update) {
            if (is_array($duplicat_update)) {
                $duplicat_update = array_map(function ($item) {
                    return "{$item} = values({$item})";
                }, $duplicat_update);
                $update_string = implode(',', $duplicat_update);
            } else {
                $update_string = $duplicat_update;
            }
            $sql .= "  on DUPLICATE KEY UPDATE {$update_string}";
        }
        return Di::getDefault()->getShared($class->getWriteConnectionService())->execute($sql);
    }

    /**
     * 批量更新
     * @param string $conditions 条件
     * @param string|array $update 更新
     * @return false|Model\QueryInterface
     */
    public static function handleUpdate(string $conditions = '', $update = [])
    {
        if (empty($conditions) || empty($update)) {
            return false;
        }
        $update_string = [];
        if (is_array($update)) {
            $update = array_map(function ($item) {
                return sprintf("'%s'", htmlspecialchars($item, ENT_QUOTES));
            }, $update);
            foreach ($update as $key => $value) {
                $update_string[] = "`{$key}` = {$value}";
            }
            $update_string = implode(',', $update_string);
        } else {
            $update_string = $update;
        }
        $class = new static();
        $sql = sprintf("UPDATE %s SET %s WHERE %s", $class->getSource(), $update_string, $conditions);
        return Di::getDefault()->getShared($class->getWriteConnectionService())->execute($sql);
    }

    /**
     * 批量删除
     * @param string $conditions
     * @return false
     */
    public static function handleBatchDelete(string $conditions = ''): bool
    {
        if (empty($conditions)) {
            return false;
        }
        $class = new static();
        $sql = sprintf("DELETE FROM %s WHERE %s", $class->getSource(), $conditions);
        return Di::getDefault()->getShared($class->getWriteConnectionService())->execute($sql);
    }
}
