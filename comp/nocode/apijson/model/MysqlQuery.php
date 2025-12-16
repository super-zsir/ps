<?php

namespace Imee\Comp\Nocode\Apijson\Model;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;
use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Comp\Nocode\Apijson\Interfaces\QueryInterface;
use Imee\Exception\ApiException;
use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Di;
use PDO;

class MysqlQuery implements QueryInterface
{
    /** @var int 查询行为 */
    const ACTION_QUERY = 1;

    /** @var int 修改行为 */
    const ACTION_UPDATE = 2;

    /** @var int 插入行为 */
    const ACTION_INSERT = 4;

    /** @var string $primaryKey */
    protected $primaryKey = 'id';

    /** @var bool $build 是否已经生成条件 */
    protected $build = false;

    /** @var Builder $builder */
    protected $builder;

    /** @var string */
    protected $tableName;

    /** @var ConditionEntity */
    protected $conditionEntity;

    /** @var AbstractPdo */
    protected $db;
    protected $bindings = [];
    protected $rowCount = 0;

    /** @var TableEntity */
    protected $tableEntity;

    /**
     * @param string $dbServiceName
     * @param string $tableName
     * @param ConditionEntity $conditionEntity
     * @param string $primaryKey
     */
    public function __construct(TableEntity $tableEntity)
    {
        $this->tableEntity = $tableEntity;
        $tableName = $this->tableEntity->getRealTableName();
        $converted = $this->camelToUnderscore($tableName);

        $this->db = Di::getDefault()->get($this->tableEntity->getDbServiceName());
        $this->tableName = $converted;
        $this->conditionEntity = $this->tableEntity->getConditionEntity();
        $this->primaryKey = $this->tableEntity->getPrimaryKey();
    }

    /**
     * @param string $primaryKey
     */
    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function all(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->executeQuery($sql, $this->bindings)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count($column = '*'): int
    {
        $sql = "SELECT COUNT({$column}) AS count FROM {$this->tableName}";
        $sql .= $this->buildWhereClause();
        $sql .= $this->buildGroupClause();

        $stmt = $this->executeQuery($sql, $this->bindings);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function toSql(): string
    {
        $this->buildSelectQuery(); // 确保 bindings 已生成
        $sql = $this->buildSelectQuery();

        // 将参数绑定到 SQL 中（仅用于显示，不用于实际执行）
        $boundSql = $sql;
        foreach ($this->bindings as $param) {
            $param = is_string($param) ? "'{$param}'" : $param;
            $boundSql = preg_replace('/\?/', $param, $boundSql, 1);
        }

        return $boundSql;
    }

    public function insert(array $values, $sequence = null): int
    {
        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO {$this->tableName} ({$columns}) VALUES ({$placeholders})";
        $this->executeQuery($sql, array_values($values));

        return $this->db->lastInsertId($sequence);
    }

    public function update(array $values): bool
    {
        if (!$this->hasWhereConditions()) {
            throw new ApiException(ApiException::MSG_ERROR, 'Update operations require WHERE conditions');
        }

        $setParts = [];
        $bindValues = [];
        foreach ($values as $column => $value) {
            $setParts[] = "{$column} = ?";
            $bindValues[] = $value;
        }

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts);
        $sql .= $this->buildWhereClause();

        // 合并 SET 和 WHERE 的参数
        $bindings = array_merge($bindValues, $this->bindings);

        $stmt = $this->executeQuery($sql, $bindings);
        $this->rowCount = $stmt->rowCount();
        return $this->rowCount > 0;
    }

    public function delete($id = null): bool
    {
        if (is_null($id) && !$this->hasWhereConditions()) {
            throw new ApiException(ApiException::MSG_ERROR, 'Delete operations require WHERE conditions or ID');
        }

        if (!is_null($id)) {
            $this->bindings = [$id];
            $where = " WHERE {$this->primaryKey} = ?";
        } else {
            $where = $this->buildWhereClause();
        }

        $sql = "DELETE FROM {$this->tableName}" . $where;
        $stmt = $this->executeQuery($sql, $this->bindings);
        $this->rowCount = $stmt->rowCount();
        return $this->rowCount > 0;
    }

    public function replace(array $values, $sequence = null): int
    {
        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "REPLACE INTO {$this->tableName} ({$columns}) VALUES ({$placeholders})";
        $this->executeQuery($sql, array_values($values));

        return $this->db->lastInsertId($sequence);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getWhereKeys(): array
    {
        $keys = [];
        $conditions = $this->conditionEntity->getCondition();
        foreach ($conditions as $key => $value) {
            // 剔除所有以 @ 开头的特殊指令
            if (strpos($key, '@') !== 0) {
                // 移除操作符，得到纯粹的字段名
                $pureKey = preg_replace('/[!$}{%~<>]*$/', '', $key);
                // 处理 OR 条件 "key1|key2"
                if (strpos($pureKey, '|') !== false) {
                    $keys = array_merge($keys, explode('|', $pureKey));
                } else {
                    $keys[] = $pureKey;
                }
            }
        }
        return array_unique($keys);
    }

    public function setColumns(string $columns): void
    {
        $this->conditionEntity->setColumn($columns);
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * 检查某些字段值是否已存在
     * @param array $where
     * @return bool
     */
    public function exists(array $where): bool
    {
        if (empty($where)) return false;
        $columns = array_keys($where);
        $conditions = [];
        $bindings = [];
        foreach ($columns as $col) {
            $conditions[] = "`{$col}` = ?";
            $bindings[] = $where[$col];
        }
        $sql = "SELECT 1 FROM {$this->tableName} WHERE " . implode(' AND ', $conditions) . " LIMIT 1";
        //exit('[DEBUG] EXISTS SQL: ' . $sql . ' | params: ' . json_encode($bindings));
        $stmt = $this->executeQuery($sql, $bindings);
        return (bool)$stmt->fetch();
    }

    protected function buildSelectQuery(): string
    {
        $columns = $this->conditionEntity->getColumn() ?: '*';
        
        // 处理聚合模式 (@sum, @count 等)
        $aggregateMode = $this->conditionEntity->getAggregateMode();
        if ($aggregateMode) {
            $type = $aggregateMode['type'];
            $fields = $aggregateMode['fields'];
            
            if ($type === 'sum') {
                $sumFields = array_map(function($field) {
                    return "SUM(`{$field}`) AS sum_{$field}";
                }, $fields);
                $columns = implode(', ', $sumFields);
            } elseif ($type === 'count') {
                $columns = 'COUNT(*) AS count';
            }
        }
        
        // 处理去重模式 (@distinct)
        $distinctMode = $this->conditionEntity->getDistinctMode();
        if ($distinctMode) {
            $distinctFields = array_map(function($field) {
                return "`{$field}`";
            }, $distinctMode);
            $columns = 'DISTINCT ' . implode(', ', $distinctFields);
        }
        
        // 处理字段别名 (@alias)
        $fieldAliases = $this->conditionEntity->getFieldAliases();
        if ($fieldAliases && $columns !== '*') {
            $aliasFields = [];
            $fieldList = explode(',', $columns);
            foreach ($fieldList as $field) {
                $field = trim($field);
                
                // 移除已有的反引号，获取原始字段名
                $rawField = str_replace(['`', ' '], '', $field);
                
                // 检查是否已经包含 AS 别名
                if (strpos($field, ' AS ') !== false) {
                    // 已经包含别名，直接使用
                    $aliasFields[] = $field;
                } elseif (isset($fieldAliases[$rawField])) {
                    // 需要应用别名
                    $cleanField = "`{$rawField}`";
                    $aliasFields[] = "{$cleanField} AS `{$fieldAliases[$rawField]}`";
                } else {
                    // 不需要别名，但确保正确格式化
                    if (strpos($field, '`') === false && $rawField !== '*') {
                        $aliasFields[] = "`{$rawField}`";
                    } else {
                        $aliasFields[] = $field;
                    }
                }
            }
            $columns = implode(', ', $aliasFields);
        }
        
        if ($this->tableEntity && $columns !== '*' && !$aggregateMode && !$distinctMode) {
            // 如果 columns 已经包含反引号（来自 @column 安全处理），或包含 AS/函数，则跳过过滤，避免去掉反引号导致关键字冲突
            $hasFunctions = $this->containsFunctions($columns);
            $hasAlias = strpos($columns, ' AS ') !== false;
            $hasBackticks = strpos($columns, '`') !== false;
            if ($hasAlias || $hasFunctions || $hasBackticks) {
                // 已经是安全列串，直接使用
            } else {
                $columns = $this->tableEntity->filterColumns($columns);
                if (is_array($columns)) {
                    $columns = implode(', ', $columns);
                }
            }
        }
        
        // 处理执行计划模式 (@explain)
        $explainPrefix = '';
        if ($this->conditionEntity->isExplainMode()) {
            $explainPrefix = 'EXPLAIN ';
        }
        
        $sql = "{$explainPrefix}SELECT {$columns} FROM {$this->tableName}";
        $sql .= $this->buildJoinClause();
        $sql .= $this->buildWhereClause();
        $sql .= $this->buildGroupClause();
        $sql .= $this->buildOrderClause();
        $sql .= $this->buildLimitClause();
        return $sql;
    }

    protected function buildJoinClause(): string
    {
        $joins = $this->conditionEntity->getJoins();
        if (empty($joins)) {
            return '';
        }
        
        $joinParts = [];
        foreach ($joins as $join) {
            $joinParts[] = " {$join['type']} JOIN {$join['table']} ON {$join['on']}";
        }

        return implode('', $joinParts);
    }

    protected function buildWhereClause(): string
    {
        $this->bindings = [];
        $whereParts = [];

        foreach ($this->conditionEntity->getQueryWhere() as $where) {
            $whereParts[] = $where['sql'];
            $this->bindings = array_merge($this->bindings, (array)$where['bind']);
        }

        return $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';
    }

    protected function buildGroupClause(): string
    {
        $groups = $this->conditionEntity->getGroup();
        if (empty($groups)) {
            return '';
        }

        // 过滤空值和非法字段
        $validGroups = array_filter($groups, function ($field) {
            return !empty($field) && is_string($field);
        });

        if (empty($validGroups)) {
            return '';
        }

        $groupClause = ' GROUP BY ' . implode(', ', array_map(
                function ($field) {
                    return '`' . str_replace('`', '', $field) . '`'; // 简单转义
                },
                $validGroups
            ));

        $having = $this->conditionEntity->getHaving();
        $havingClause = '';

        if (!empty($having) && !empty($having['sql'])) {
            $havingClause = ' HAVING ' . $having['sql'];
            $this->bindings = array_merge($this->bindings, (array)$having['bind']);
        }

        return $groupClause . $havingClause;
    }

    protected function buildOrderClause(): string
    {
        $orders = $this->conditionEntity->getOrder();
        if (empty($orders)) {
            return '';
        }

        $orderParts = [];
        foreach ($orders as $order) {
            $orderParts[] = "{$order[0]} {$order[1]}";
        }

        return ' ORDER BY ' . implode(', ', $orderParts);
    }

    protected function buildLimitClause(): string
    {
        $limit = $this->conditionEntity->getLimit();
        $offset = $this->conditionEntity->getOffset();

        if ($limit <= 0) {
            return '';
        }

        return $offset > 0
            ? " LIMIT {$offset}, {$limit}"
            : " LIMIT {$limit}";
    }

    protected function hasWhereConditions(): bool
    {
        return !empty($this->conditionEntity->getQueryWhere());
    }

    protected function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        //exit('[DEBUG] EXECUTE SQL: ' . $sql . ' | params: ' . json_encode($params));
        try {
            $stmt = $this->db->prepare($sql);
            if ($stmt === false) {
                $err = method_exists($this->db, 'errorInfo') ? $this->db->errorInfo() : [];
                $msg = isset($err[2]) ? $err[2] : 'unknown prepare error';
                throw new \RuntimeException("SQL prepare failed: {$msg}");
            }
            if ($stmt->execute($params) === false) {
                $err = method_exists($stmt, 'errorInfo') ? $stmt->errorInfo() : [];
                $msg = isset($err[2]) ? $err[2] : 'unknown execute error';
                throw new \RuntimeException("SQL execute failed: {$msg}");
            }
            return $stmt;
        } catch (\Throwable $e) {
            // 统一抛出包含 SQL 与参数的错误，便于定位
            $context = json_encode($params, JSON_UNESCAPED_UNICODE);
            throw new \RuntimeException("SQL Error: {$e->getMessage()} | SQL: {$sql} | params: {$context}", 0, $e);
        }
    }

    private function camelToUnderscore(string $input): string
    {
        // 将驼峰转为下划线，CmsUser -> cms_user，CmsUserRolesNew -> cms_user_roles_new
        $output = preg_replace('/([A-Z])/', '_$1', $input);
        $output = ltrim($output, '_');
        return strtolower($output);
    }

    /**
     * 检测字符串中是否包含函数调用
     * 能正确处理包含引号、逗号的复杂函数参数
     * 
     * @param string $columns
     * @return bool
     */
    private function containsFunctions(string $columns): bool
    {
        // 简单检测：如果包含函数名模式，进一步验证
        if (!preg_match('/[A-Z]+\s*\(/i', $columns)) {
            return false;
        }
        
        // 分割多个字段
        $fields = explode(',', $columns);
        foreach ($fields as $field) {
            $field = trim($field);
            
            // 检查是否包含函数调用
            if (preg_match('/^[A-Z]+\s*\(/i', $field)) {
                // 验证括号是否匹配
                if ($this->isValidFunctionCall($field)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 验证函数调用是否合法
     * 
     * @param string $functionCall
     * @return bool
     */
    private function isValidFunctionCall(string $functionCall): bool
    {
        $functionCall = trim($functionCall);
        
        // 移除可能的别名部分
        if (preg_match('/^(.+?)\s+AS\s+/i', $functionCall, $matches)) {
            $functionCall = trim($matches[1]);
        }
        
        // 验证括号匹配
        $openCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($functionCall); $i++) {
            $char = $functionCall[$i];
            
            // 处理引号
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                continue;
            } elseif ($char === $quoteChar && $inQuotes) {
                $inQuotes = false;
                $quoteChar = '';
                continue;
            }
            
            // 在引号内时跳过括号检查
            if ($inQuotes) {
                continue;
            }
            
            // 计算括号
            if ($char === '(') {
                $openCount++;
            } elseif ($char === ')') {
                $openCount--;
                if ($openCount === 0) {
                    // 找到匹配的结束括号
                    return true;
                }
            }
        }
        
        return false;
    }
}