<?php

namespace Imee\Comp\Common\Orm\Traits;

use Phalcon\Di;

trait MysqlCollectionTrait
{
    public static $opMapping = [
        'EQ'          => '=',
        'NEQ'         => '!=',
        'GT'          => '>',
        'LT'          => '<',
        'GTE'         => '>=',
        'LTE'         => '<=',
        'EGT'         => '>=',
        'ELT'         => '<=',
        'IN'          => 'IN',
        'NOT IN'      => 'NOT IN',
        '='           => '=',
        '<>'          => '<>',
        '!='          => '!=',
        '>'           => '>',
        '<'           => '<',
        '>='          => '>=',
        '<='          => '<=',
        'LIKE'        => 'LIKE',
        'LLIKE'       => 'LIKE',
        'RLIKE'       => 'LIKE',
        'IS NULL'     => 'IS NULL',
        'IS NOT NULL' => 'IS NOT NULL',
        'FIND_IN_SET' => 'FIND_IN_SET',
        'FIELD'       => 'FIELD',
        'UNFIELD'     => 'UNFIELD',
        'BIT'         => 'BIT',
    ];

    /**
     * 获取列表和总数
     * @param array $condition
     * $condition = [];
     * $condition[] = ['time', '>=', $endTime]
     * $condition[] = ['time', '=', $endTime]
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getListAndTotal(array $condition, string $field = '*', string $order = '', int $page = 0, int $pageSize = 0): array
    {
        $model = static::query();
        $order = trim($order);

        // 设置字段
        $field && $model->columns($field);

        $bind = [];
        // region condition start
        if (!empty($condition)) {
            list($model, $bind) = self::parseCondition($model, $condition);
        }

        $total = self::count([
            'conditions' => $model->getConditions(),
            'bind'       => $bind,
        ]);

        if (!$total) {
            return ['data' => [], 'total' => 0];
        }

        if (!empty($order)) {
            $model->orderBy($order);
        }
        if ($page && $pageSize) {
            $startLimit = ($page - 1) * $pageSize;
            $model->limit($pageSize, $startLimit);
        }
        $data = $model->execute()->toArray();
        return ['data' => $data, 'total' => $total];
    }

    public static function getCount($condition = []): int
    {
        $model = static::query();
        $bind = [];
        if (!empty($condition)) {
            list($model, $bind) = self::parseCondition($model, $condition);
        }
        return self::count([
            'conditions' => $model->getConditions(),
            'bind'       => $bind,
        ]);
    }

    public static function findOne($id, $isMaster = false): array
    {
        $pk = static::$primaryKey ?? 'id';
        if ($isMaster) {
            $builder = static::useMaster();
        } else {
            $builder = static::class;
        }
        $info = $builder::findFirst([
            'conditions' => "{$pk} = :{$pk}:",
            'bind'       => [$pk => $id],
        ]);

        if (!$info) {
            return [];
        }
        return $info->toArray();
    }

    public static function findOneByWhere($condition, $field = '*', $order = '', $isMaster = false): array
    {
        if ($field === true) {
            $field = '*';
            $isMaster = true;
            $order = '';
        }

        $info = self::findFirstByWhere($condition, $field, $order, $isMaster);
        return $info ? $info->toArray() : [];
    }

    public static function findFirstByWhere($condition = [], string $field = '*', $order = '', $isMaster = true)
    {
        if (!$condition) {
            return false;
        }

        if ($isMaster) {
            $model = static::useDb(self::SCHEMA)->query();
        } else {
            $model = static::query();
        }

        $field && $model->columns($field);
        list($model, $_) = self::parseCondition($model, $condition);
        if (!empty($order)) {
            $model->orderBy($order);
        }
        $model->limit(1, 0);
        return $model->execute()->getFirst();
    }

    public static function findByIds($id, $columns = '*'): array
    {
        if (!$id) {
            return [];
        }
        if (!is_array($id)) {
            $id = [$id];
        } else {
            $id = array_filter($id);
            $id = array_unique($id);
            $id = array_values($id);
        }
        $pk = static::$primaryKey ?? 'id';
        return static::find([
            'columns'    => $columns,
            'conditions' => "{$pk} in ({{$pk}:array})",
            'bind'       => [$pk => $id],
        ])->toArray();
    }

    public static function findAll(): array
    {
        return static::find()->toArray();
    }

    /**
     * 批量插入
     * INSERT IGNORE 忽略错误插入
     * @param array $data
     * @param string $op
     * @param array|string $duplicateUpdate
     * @return array
     */
    public static function addBatch(array $data, string $op = 'INSERT', $duplicateUpdate = ''): array
    {
        if (!$data) {
            return [false, 'data 不能为空', 0];
        }
        $table = static::getTableName();
        $schema = self::SCHEMA;
        $keyNames = array_keys($data[0]);
        $keys = array_map(function ($key) {
            return "`{$key}`";
        }, $keyNames);
        $keys = implode(',', $keys);
        $sql = "{$op} INTO {$table} ({$keys}) VALUES ";
        foreach ($data as $v) {
            $v = array_map(function ($value) {
                return "'" . addslashes(trim($value)) . "'";
            }, $v);
            $values = implode(',', array_values($v));
            $sql .= " ({$values}), ";
        }
        $sql = rtrim(trim($sql), ',');
        if ($duplicateUpdate) {
            if (is_array($duplicateUpdate)) {
                $duplicateUpdate = array_map(function ($item) {
                    return "{$item} = values({$item})";
                }, $duplicateUpdate);
                $updateString = implode(',', $duplicateUpdate);
            } else {
                $updateString = $duplicateUpdate;
            }
            $sql .= "  on DUPLICATE KEY UPDATE {$updateString}";
        }
        $rows = 0;
        try {
            $conn = Di::getDefault()->getShared($schema);
            if ($conn->execute($sql)) {
                $rows = $conn->affectedRows();
            }
        } catch (\PDOException $exception) {
            return [false, $exception->getMessage(), 0];
        }
        return [true, '', $rows];
    }

    /**
     * 批量更新
     *
     * $tmp = [];
     * $tmp['state_check'] = $val['mid']<$maxId ? 0 : 1;
     * $tmp['resource_type'] = LbShowMusic::getResourceType($val);
     * $list[$val['id']] = $tmp;
     *
     * @param $list
     * @return array
     */
    public static function updateBatch($list): array
    {
        $rows = 0;
        if (!$list) {
            return [false, '更新内容不能为空', $rows];
        }
        $table = static::getTableName();
        $schema = self::SCHEMA;
        $pk = static::$primaryKey ?? 'id';

        $sqlSet = [];
        $pkVals = [];
        foreach ($list as $pkVal => $val) {
            $pkVals[] = $pkVal;
            foreach ($val as $updateKey => $updateVal) {
                $updateVal = addslashes(trim($updateVal));
                $sqlSet[$updateKey][] = " WHEN {$pkVal} THEN '{$updateVal}'";
            }
        }
        if ($pkVals && $sqlSet) {
            $sqlSetNew = [];
            foreach ($sqlSet as $updateKey => $updateList) {
                $sqlSetNew[] = "{$updateKey} = CASE {$pk} " . implode('', $updateList);
            }
            $sqlSetStr = implode('END,', $sqlSetNew);
            $sql = "UPDATE {$table} SET {$sqlSetStr}";
            $pkValStr = implode(',', $pkVals);
            $sql .= " END WHERE {$pk} IN ( {$pkValStr} )";
            try {
                $conn = Di::getDefault()->getShared($schema);
                if ($conn->execute($sql)) {
                    $rows = $conn->affectedRows();
                }
            } catch (\PDOException $exception) {
                return [false, $exception->getMessage(), $rows];
            }
        }
        return [true, '', $rows];
    }

    /**
     * 按条件更新
     * 最大每次更新1w条，如果超过这个量的请查出来后分批按主键id批量更新
     * @param array $condition
     * @param array $update
     * @param int $limit
     * @return array
     */
    public static function updateByWhere(array $condition, array $update, int $limit = 10000): array
    {
        $rows = 0;
        if (!$condition || !$update) {
            return [false, 'condition/update 不能为空', $rows];
        }
        $where = self::parseWhere($condition);
        $table = static::getTableName();
        $schema = self::SCHEMA;
        $updateStr = [];
        $update = array_map(function ($item) {
            return sprintf("'%s'", htmlspecialchars($item, ENT_QUOTES));
        }, $update);
        foreach ($update as $key => $value) {
            $updateStr[] = "`{$key}` = {$value}";
        }
        $updateStr = implode(',', $updateStr);
        $sql = sprintf("UPDATE %s SET %s WHERE %s LIMIT %s", $table, $updateStr, $where, $limit);
        try {
            $conn = Di::getDefault()->getShared($schema);
            if ($conn->execute($sql)) {
                $rows = $conn->affectedRows();
            }
        } catch (\PDOException $exception) {
            return [false, $exception->getMessage(), $rows];
        }
        return [true, '', $rows];
    }

    protected static function parseWhere($condition): string
    {
        $model = static::query();
        list($model, $bind) = self::parseCondition($model, $condition);
        $where = $model->getConditions();
        foreach ($bind as $key => $val) {
            if (is_array($val)) {
                $key = "{{$key}:array}";
                $val = array_map(function ($v) {
                    return "'{$v}'";
                }, $val);
                $val = implode(',', $val);
            } else {
                $key = ":{$key}:";
                $val = "'{$val}'";
            }
            $where = str_replace($key, $val, $where);
        }

        return $where;
    }

    /**
     * 批量删除
     * @param array $condition
     * @param int $limit
     * $condition = [];
     * $condition[] = ['time', '>=', $endTime]
     * $condition[] = ['time', '=', $endTime]
     * @param int $usleep
     * @return array
     */
    public static function deleteByWhere(array $condition, int $limit = 2000, $usleep = 100000): array
    {
        if (!$condition) {
            return [false, 'condition 不能为空', 0];
        }
        $where = self::parseWhere($condition);
        $table = static::getTableName();
        $schema = self::SCHEMA;
        $count = 0;
        try {
            $conn = Di::getDefault()->getShared($schema);
            $sql = "DELETE FROM {$table} WHERE {$where} LIMIT {$limit}";
            while (true) {
                $rows = 0;
                if ($conn->execute($sql)) {
                    $rows = $conn->affectedRows();
                }
                $count += $rows;
                if ($rows < $limit) {
                    break;
                }
                usleep($usleep);
            }
        } catch (\PDOException $exception) {
            return [false, $exception->getMessage(), 0];
        }
        return [true, '', $count];
    }

    /**
     * 获取列表
     * @param array $condition
     * $condition = [];
     * $condition[] = ['time', '>=', $endTime]
     * $condition[] = ['time', '=', $endTime]
     * @param string $field
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @param string $groupBy
     * @return array
     */
    public static function getListByWhere(array $condition, string $field = '*', string $order = '', int $limit = 0, int $offset = 0, string $groupBy = ''): array
    {
        $model = static::query();
        $order = trim($order);

        // 设置字段
        $field && $model->columns($field);

        // region condition start
        if (!empty($condition)) {
            list($model, $_) = self::parseCondition($model, $condition);
        }

        if ($groupBy) {
            $model->groupBy($groupBy);
        }

        if (!empty($order)) {
            $model->orderBy($order);
        }

        if ($limit) {
            $model->limit($limit, $offset ?? 0);
        }
        return $model->execute()->toArray();
    }

    /**
     * 迭代返回list
     *
     * 本方法使用范围：
     * 1.纯主键全表扫描
     * 2.唯一索引字段遍历 必须指定迭代排序字段 $sortField
     * 3.联合索引的必须有一个字段可以排序唯一，多个where条件的 谨慎使用 必须指定迭代排序字段 $sortField
     *
     * @param array $condition
     * @param string $field
     * @param int $limit
     * @param string $sortField
     * @return \Generator
     */
    public static function getGeneratorListByWhere(array $condition, string $field = '*', int $limit = 1000, string $sortField = ''): \Generator
    {
        foreach (self::getGeneratorObjByWhere($condition, $field, $limit, $sortField) as $getGenerator) {
            yield $getGenerator->toArray();
        }
    }

    /**
     * 迭代返回list obj
     *
     * 本方法使用范围：
     * 1.纯主键全表扫描
     * 2.唯一索引字段遍历 必须指定迭代排序字段 $sortField
     * 3.联合索引的必须有一个字段可以排序唯一，多个where条件的 谨慎使用 必须指定迭代排序字段 $sortField
     *
     * @param array $condition
     * @param string $field
     * @param int $limit
     * @param string $sortField
     * @return \Generator
     */
    public static function getGeneratorObjByWhere(array $condition, string $field = '*', int $limit = 1000, string $sortField = ''): \Generator
    {
        $pk = static::$primaryKey ?? 'id';

        if (empty($sortField)) {
            $sortField = $pk . ' asc';
        }
        if ($field != '*') {
            $fields = explode(',', $field);
            if (!in_array($pk, $fields)) {
                $fields = array_merge($fields, [$pk]);
            }
            $field = implode(',', $fields);
        }
        $pkValue = 0;
        while (true) {
            $model = static::query();
            $model->columns($field);
            $pkValue > 0 && $model->andWhere("{$pk} > :{$pk}:", [$pk => $pkValue]);
            if (!empty($condition)) {
                list($model, $_) = self::parseCondition($model, $condition);
            }
            $model->orderBy($sortField);
            if ($limit) {
                $startLimit = 0;
                $model->limit($limit, $startLimit);
            }
            yield $data = $model->execute();
            $data = $data->toArray();
            if (count($data) < $limit) {
                break;
            }
            $max = end($data);
            $pkValue = $max[$pk];
        }
    }

    public static function addOrEdit($id, array $data): array
    {
        $pk = static::$primaryKey ?? 'id';
        $rec = static::useDb(self::SCHEMA)
            ->findFirst([
                "conditions" => "{$pk} = :id:",
                "bind"       => ["id" => $id],
            ]);

        if (!$rec) {
            return self::add($data);
        }

        foreach ($data as $key => $value) {
            $rec->$key = $value;
        }

        try {
            $rec->save();
            return [true, $id];
        } catch (\PDOException $e) {
            return [false, $e->getMessage()];
        }
    }

    public static function add($data): array
    {
        $pk = static::$primaryKey ?? 'id';
        $model = static::useDb(self::SCHEMA);

        //支持配置创建时间字段
        $createTime = static::$createTime ?? '';
        if ($createTime) {
            $data[$createTime] = time();
        }

        //兼容字段里有source报错问题
        if (isset($data['source'])) {
            try {
                foreach ($data as $k => $v) {
                    $model->{$k} = $v;
                }
                $model->save();
                return [true, $model->$pk];
            } catch (\PDOException $e) {
                return [false, $e->getMessage()];
            }
        }

        try {
            $insertId = 0;
            if ($model->create($data)) {
                $insertId = $model->$pk;
            }
            return [true, $insertId];
        } catch (\PDOException $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * 新增-若存在就更新
     * @param array $data
     * @param array $where 条件过滤必须唯一
     * @return array
     */
    public static function addRow(array $data, array $where = []): array
    {
        if ($where) {
            $condition = [];
            foreach ($where as $k => $v) {
                $condition[] = [$k, '=', $v];
            }
            $rec = self::findFirstByWhere($condition);
            if ($rec) {
                try {
                    foreach ($data as $k => $v) {
                        if ($rec->$k != $v) {
                            $rec->$k = $v;
                        }
                    }
                    $result = $rec->save();
                    return [true, $result];
                } catch (\PDOException $e) {
                    return [false, $e->getMessage()];
                }
            }
            $data = array_merge($data, $where);
        }
        return self::add($data);
    }

    public static function edit($id, $data): array
    {
        $pk = static::$primaryKey ?? 'id';
        //支持配置更新时间字段
        $updateTime = static::$updateTime ?? '';
        if ($updateTime) {
            $data[$updateTime] = time();
        }
        $rec = static::useDb(self::SCHEMA)
            ->findFirst([
                "conditions" => "{$pk} = :id:",
                "bind"       => ["id" => $id],
            ]);

        if (!$rec) {
            return [false, '未查到该id记录'];
        }

        foreach ($data as $key => $value) {
            $rec->$key = $value;
        }

        try {
            $result = $rec->save();
            return [true, $result];
        } catch (\PDOException $e) {
            return [false, $e->getMessage()];
        }
    }

    public static function deleteById($id): bool
    {
        $pk = static::$primaryKey ?? 'id';
        $rec = static::useDb(self::SCHEMA)
            ->findFirst([
                "conditions" => "{$pk} = :id:",
                "bind"       => ["id" => $id],
            ]);

        if (!$rec) {
            return false;
        }

        return $rec->delete();
    }

    public static function deleteBatch($ids, $chunk = 200, $usleep = 100000): bool
    {
        if (!$ids) {
            return false;
        }
        $pk = static::$primaryKey ?? 'id';
        $list = array_chunk($ids, $chunk);
        $dbObj = static::useDb(self::SCHEMA);
        foreach ($list as $ids) {
            $dbObj->find([
                "conditions" => "{$pk} in ({id:array})",
                "bind"       => ["id" => $ids],
            ])->delete();
            usleep($usleep);
        }
        return true;
    }

    public static function getBatchCommon($ids, $fields = [], $searchKey = null): array
    {
        if (empty($ids)) {
            return [];
        }
        if (!$searchKey) {
            $searchKey = static::$primaryKey ?? 'id';
        }
        if (!in_array($searchKey, $fields)) {
            $fields [] = $searchKey;
        }
        $result = static::find(["{$searchKey} in ({id:array})", 'bind' => ['id' => $ids], 'columns' => $fields])->toArray();
        return array_column($result, null, $searchKey);
    }

    public static function fetchAllBySql($sql, array $bind = null, $schema = ''): array
    {
        if (!$schema) {
            $schema = static::SCHEMA_READ;
        }
        $conn = Di::getDefault()->getShared($schema);
        return $conn->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bind);
    }

    public static function fetchOneBySql($sql, array $bind = null, $schema = '')
    {
        if (!$schema) {
            $schema = static::SCHEMA_READ;
        }
        $conn = Di::getDefault()->getShared($schema);
        return $conn->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bind);
    }

    public static function fetchColumnBySql($sql, $schema = '')
    {
        if (!$schema) {
            $schema = static::SCHEMA_READ;
        }
        $conn = Di::getDefault()->getShared($schema);
        return $conn->fetchColumn($sql);
    }

    public static function execBySql($sql, $schema = ''): int
    {
        if (!$schema) {
            $schema = static::SCHEMA;
        }
        $conn = Di::getDefault()->getShared($schema);
        if ($conn->execute($sql)) {
            return $conn->affectedRows();
        }
        return 0;
    }

    protected static function parseCondition($model, $condition): array
    {
        $func = function ($symbol, $key, $value) use (&$model, &$bind) {
            $symbolMap = self::_comparisonSymbolMap($symbol);
            $bindKey = str_replace('.', '_', $key);
            switch (strtoupper($symbol)) {
                case 'IN':
                    $bindKey .= "_IN";
                    $bindValue = !empty($value) ? $value : [-99999];
                    $model->andWhere("{$key} IN ({{$bindKey}:array})", [$bindKey => $bindValue]);
                    break;
                case 'NOT IN':
                    $bindKey .= "_NIN";
                    $bindValue = !empty($value) ? $value : [-99999];
                    $model->andWhere("{$key} NOT IN ({{$bindKey}:array})", [$bindKey => $bindValue]);
                    break;
                case 'LIKE':
                    $bindKey .= "_LIKE";
                    $bindValue = "%{$value}%";
                    $model->andWhere("{$key} LIKE :{$bindKey}:", [$bindKey => $bindValue]);
                    break;
                case 'LLIKE':
                    $bindKey .= "_LLIKE";
                    $bindValue = "{$value}%";
                    $model->andWhere("{$key} LIKE :{$bindKey}:", [$bindKey => $bindValue]);
                    break;
                case 'RLIKE':
                    $bindKey .= "_RLIKE";
                    $bindValue = "%{$value}";
                    $model->andWhere("{$key} LIKE :{$bindKey}:", [$bindKey => $bindValue]);
                    break;
                case 'IS NULL':
                    $model->andWhere("{$key} IS NULL");
                    break;
                case 'IS NOT NULL':
                    $model->andWhere("{$key} IS NOT NULL");
                    break;
                case 'FIND_IN_SET':
                    $bindValue = (string)$value;
                    $model->andWhere("FIND_IN_SET(:{$bindKey}:, {$key})", [$bindKey => $bindValue]);
                    break;
                case 'FIELD':
                    $model->andWhere("{$key} = {$value}");
                    break;
                case 'UNFIELD':
                    $model->andWhere("{$key} <> {$value}");
                    break;
                case 'BIT':
                    $model->andWhere("{$key} & {$value} = {$value}");
                    break;
                default:
                    $defaultSymbolBindMap = [
                        '!=' => 'NEQ',
                        '<>' => 'NEQ',
                        '>'  => 'GT',
                        '>=' => 'GTE',
                        '<'  => 'LT',
                        '<=' => 'LTE',
                        '='  => 'EQ',
                    ];
                    $bindKey .= $defaultSymbolBindMap[$symbolMap] ?? '';
                    $bindValue = $value;
                    $model->andWhere("{$key} {$symbolMap} :{$bindKey}:", [$bindKey => $bindValue]);
                    break;
            }
            isset($bindValue) && $bind[$bindKey] = $bindValue;
        };

        foreach ($condition as $item) {
            if (!is_array($item)) {
                continue;
            }
            $count = count($item);
            if ($count < 2 || $count > 3) {
                continue;
            }
            // 解构数组元素，并根据参数个数设置默认值
            if ($count === 2) {
                $opNullMap = ['IS NULL', 'IS NOT NULL'];
                if (in_array(strtoupper($item[1]), $opNullMap, true)) {
                    $item = [$item[0], $item[1], null];
                } else {
                    $item = [$item[0], '=', $item[1]];
                }
            }
            list($key, $symbol, $value) = $item;
            $func($symbol, $key, $value);
        }

        return [$model, $bind];
    }

    /**
     * 获取比较符号
     * @param null $symbol
     * @return string
     */
    private static function _comparisonSymbolMap($symbol = null): string
    {
        $symbol = strtoupper($symbol);
        $map = self::$opMapping;
        return $map[$symbol] ?? $map['EQ'];
    }
}
