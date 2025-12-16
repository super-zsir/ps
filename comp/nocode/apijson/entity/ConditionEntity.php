<?php

namespace Imee\Comp\Nocode\Apijson\Entity;

use Imee\Exception\ApiException;

class ConditionEntity
{
    protected $changeLog = [];

    /**
     * @var array
     */
    protected $where = [];

    protected $limit = 10;
    protected $offset = 0;
    protected $column = '*';
    protected $group = [];
    protected $order = [];
    protected $having = [];
    protected $procedure = "";
    protected $joins = [];
    protected $countMode = false;
    protected $aggregateMode = null;
    protected $distinctMode = null;
    protected $fieldAliases = null;
    protected $explainMode = false;

    /**
     * @param array $condition 条件
     */
    protected $condition;

    protected $extendData;

    protected $tableName;

    public function __construct(array $condition, array $extendData = [], string $tableName = '')
    {
        $this->condition = $condition;
        $this->extendData = $extendData;
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getExtendData(): array
    {
        return $this->extendData;
    }

    public function setCondition(array $condition)
    {
        $this->log($condition);
        $this->condition = $condition;
    }

    public function getCondition(): array
    {
        return $this->condition;
    }

    public function getQueryWhere(): array
    {
        return $this->where;
    }

    public function setQueryWhere(array $where)
    {
        $this->where = $where;
    }

    public function addQueryWhere(string $key, string $sql, array $bindArgs = [])
    {
        $this->where[$key] = [
            'sql' => $sql,
            'bind' => $bindArgs
        ];
    }

    /**
     * @param string $column
     */
    public function setColumn(string $column): void
    {
        $this->column = $column;
    }

    /**
     * @param array $group
     */
    public function setGroup(array $group): void
    {
        $this->group = $group;
    }

    /**
     * @param array $having
     */
    public function setHaving(array $having): void
    {
        $this->having = $having;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        if ($limit > 1000) {
            throw new ApiException(ApiException::MSG_ERROR, '单次查询最大只允许 1000 条');
        }
        $this->limit = $limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @param array $order
     */
    public function setOrder(array $order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return array
     */
    public function getGroup(): array
    {
        return $this->group;
    }

    /**
     * @return array
     */
    public function getHaving(): array
    {
        return $this->having;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @param string $procedure
     */
    public function setProcedure(string $procedure): void
    {
        $this->procedure = $procedure;
    }

    /**
     * @return string
     */
    public function getProcedure(): string
    {
        return $this->procedure;
    }

    public function addJoin(string $type, string $targetTable, string $onClause)
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $targetTable,
            'on' => $onClause,
        ];
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function setCountMode(bool $countMode): void
    {
        $this->countMode = $countMode;
    }

    public function isCountMode(): bool
    {
        return $this->countMode;
    }

    /**
     * 设置聚合模式
     * @param string $type 聚合类型 (sum, avg, max, min, count)
     * @param array $fields 聚合字段
     */
    public function setAggregateMode(string $type, array $fields): void
    {
        $this->aggregateMode = [
            'type' => $type,
            'fields' => $fields
        ];
    }

    /**
     * 获取聚合模式
     * @return array|null
     */
    public function getAggregateMode(): ?array
    {
        return $this->aggregateMode ?? null;
    }

    /**
     * 设置去重模式
     * @param array $fields 去重字段
     */
    public function setDistinctMode(array $fields): void
    {
        $this->distinctMode = $fields;
    }

    /**
     * 获取去重模式
     * @return array|null
     */
    public function getDistinctMode(): ?array
    {
        return $this->distinctMode ?? null;
    }

    /**
     * 设置字段别名
     * @param array $aliases 字段别名映射
     */
    public function setFieldAliases(array $aliases): void
    {
        $this->fieldAliases = $aliases;
    }

    /**
     * 获取字段别名
     * @return array|null
     */
    public function getFieldAliases(): ?array
    {
        return $this->fieldAliases ?? null;
    }

    /**
     * 设置执行计划模式
     * @param bool $explainMode
     */
    public function setExplainMode(bool $explainMode): void
    {
        $this->explainMode = $explainMode;
    }

    /**
     * 获取执行计划模式
     * @return bool
     */
    public function isExplainMode(): bool
    {
        return $this->explainMode ?? false;
    }

    protected function log(array $condition)
    {
        $this->changeLog[] = [
            'old' => $this->condition,
            'new' => $condition
        ];
    }
}