<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Parse\Handle;
use App\Event\ApiJson\QueryExecuteAfter;
use App\Event\ApiJson\QueryExecuteBefore;
use App\Event\ApiJson\QueryResult;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

class GetMethod extends AbstractMethod
{
    protected function validateCondition(): bool
    {
        return $this->method == 'GET';
    }

    protected function filterAtFields($data) {
        if (is_array($data)) {
            // 区分关联数组和索引数组
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            if ($isAssoc) {
                foreach ($data as $key => $value) {
                    if (is_string($key) && strpos($key, '@') === 0) {
                        unset($data[$key]);
                    } else {
                        $data[$key] = $this->filterAtFields($value);
                    }
                }
            } else {
                foreach ($data as $idx => $value) {
                    $data[$idx] = $this->filterAtFields($value);
                }
            }
        }
        return $data;
    }

    protected function process()
    {
        try {
            // 读操作使用读库
            $this->tableEntity->setDbServiceName($this->tableEntity->getReadDbServiceName());
            
            $queryMany = $this->isQueryMany();
            if (!$queryMany) {
                $this->tableEntity->getConditionEntity()->setLimit(1);
            }

            $handle = new Handle($this->tableEntity->getConditionEntity(), $this->tableEntity);
            $handle->buildQuery();

            $result = $this->query->all();

            if (!empty($this->tableEntity->getConditionEntity()->getProcedure())) {
                foreach ($result as $i => $item) {
                    $result[$i]['procedure'] = $this->query->callProcedure($item);
                }
            }

            // 检查是否为聚合查询（包括GROUP BY）
            $isAggregateQuery = $this->isAggregateQuery();
            
            // 检查是否为 COUNT 模式
            if ($this->tableEntity->isCountQuery() || $this->tableEntity->getConditionEntity()->isCountMode()) {
                // COUNT 模式，返回记录总数
                $finalResult = count($result);
                error_log("GetMethod - COUNT mode, returning count: " . $finalResult);
            } else if ($isAggregateQuery) {
                // 聚合查询模式，返回聚合结果
                $finalResult = $result;
                error_log("GetMethod - Aggregate query mode, returning result: " . json_encode($result));
            } else if ($queryMany) {
                // 对于 [] 结尾的表，始终返回数组格式
                $finalResult = $result;
            } else {
                // 查详情时返回对象或 null
                $finalResult = isset($result[0]) ? $result[0] : null;
            }
            
            // 调试信息：记录查询结果
            $tableName = $this->tableEntity->getTableName();
            error_log("GetMethod process - Table: {$tableName}, QueryMany: " . ($queryMany ? 'true' : 'false') . ", isAggregateQuery: " . ($isAggregateQuery ? 'true' : 'false') . ", Result count: " . count($result));
            
            return $this->filterAtFields($finalResult);
        } catch (\Exception $e) {
            $tableName = $this->tableEntity->getTableName();
            error_log("GetMethod process - Table: {$tableName}, Exception: " . $e->getMessage());
            error_log("GetMethod process - Table: {$tableName}, Exception trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * 检查是否为聚合查询
     * @return bool
     */
    protected function isAggregateQuery(): bool
    {
        $condition = $this->tableEntity->getConditionEntity()->getCondition();
        
        // 检查是否有聚合关键字
        $aggregateKeywords = ['@group', '@sum', '@count', '@avg', '@max', '@min'];
        foreach ($aggregateKeywords as $keyword) {
            if (isset($condition[$keyword])) {
                return true;
            }
        }
        
        // 检查@column中是否包含聚合函数
        if (isset($condition['@column'])) {
            $column = $condition['@column'];
            $aggregateFunctions = ['COUNT(', 'SUM(', 'AVG(', 'MAX(', 'MIN('];
            foreach ($aggregateFunctions as $func) {
                if (stripos($column, $func) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
}