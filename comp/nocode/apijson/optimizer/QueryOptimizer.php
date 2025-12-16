<?php

namespace Imee\Comp\Nocode\Apijson\Optimizer;

class QueryOptimizer
{
    // 批量操作阈值
    const BATCH_THRESHOLD = 100;

    // 缓存配置
    protected $cacheConfig = [];

    // 索引配置
    protected $indexConfig = [];

    public function __construct(array $config = [])
    {
        $this->cacheConfig = $config['cache'] ?? [];
        $this->indexConfig = $config['index'] ?? [];
    }

    /**
     * 优化查询
     * @param array $query
     * @return array
     */
    public function optimize(array $query): array
    {
        // 1. 优化批量操作
        $query = $this->optimizeBatchOperations($query);

        // 2. 优化字段选择
        $query = $this->optimizeFieldSelection($query);

        // 3. 优化条件顺序
        $query = $this->optimizeConditionOrder($query);

        // 4. 优化分页
        $query = $this->optimizePagination($query);

        // 5. 优化排序
        $query = $this->optimizeOrdering($query);

        return $query;
    }

    /**
     * 优化批量操作
     * @param array $query
     * @return array
     */
    protected function optimizeBatchOperations(array $query): array
    {
        foreach ($query as $tableName => $tableData) {
            if (substr($tableName, -2) === '[]') {
                // 检查是否需要分批处理
                if (is_array($tableData) && count($tableData) > self::BATCH_THRESHOLD) {
                    $query[$tableName] = $this->splitBatchData($tableData);
                }
            }
        }

        return $query;
    }

    /**
     * 分割批量数据
     * @param array $data
     * @return array
     */
    protected function splitBatchData(array $data): array
    {
        $chunks = array_chunk($data, self::BATCH_THRESHOLD);
        $result = [];

        foreach ($chunks as $index => $chunk) {
            $result["batch_{$index}"] = $chunk;
        }

        return $result;
    }

    /**
     * 优化字段选择
     * @param array $query
     * @return array
     */
    protected function optimizeFieldSelection(array $query): array
    {
        foreach ($query as $tableName => $tableData) {
            if (isset($tableData['@column'])) {
                $columns = $tableData['@column'];
                
                // 移除重复字段
                $columnArray = explode(',', $columns);
                $columnArray = array_unique($columnArray);
                $tableData['@column'] = implode(',', $columnArray);
                
                $query[$tableName] = $tableData;
            }
        }

        return $query;
    }

    /**
     * 优化条件顺序
     * @param array $query
     * @return array
     */
    protected function optimizeConditionOrder(array $query): array
    {
        foreach ($query as $tableName => $tableData) {
            $optimizedData = [];
            
            // 优先处理索引字段
            $indexFields = $this->getIndexFields($tableName);
            
            foreach ($indexFields as $field) {
                if (isset($tableData[$field])) {
                    $optimizedData[$field] = $tableData[$field];
                    unset($tableData[$field]);
                }
            }
            
            // 处理其他字段
            foreach ($tableData as $field => $value) {
                $optimizedData[$field] = $value;
            }
            
            $query[$tableName] = $optimizedData;
        }

        return $query;
    }

    /**
     * 优化分页
     * @param array $query
     * @return array
     */
    protected function optimizePagination(array $query): array
    {
        foreach ($query as $tableName => $tableData) {
            // 限制最大分页大小
            if (isset($tableData['@limit']) && $tableData['@limit'] > 1000) {
                $tableData['@limit'] = 1000;
            }
            
            // 限制最大偏移量
            if (isset($tableData['@offset']) && $tableData['@offset'] > 10000) {
                $tableData['@offset'] = 10000;
            }
            
            $query[$tableName] = $tableData;
        }

        return $query;
    }

    /**
     * 优化排序
     * @param array $query
     * @return array
     */
    protected function optimizeOrdering(array $query): array
    {
        foreach ($query as $tableName => $tableData) {
            if (isset($tableData['@order'])) {
                $order = $tableData['@order'];
                
                // 限制排序字段数量
                $orderFields = explode(',', $order);
                if (count($orderFields) > 3) {
                    $orderFields = array_slice($orderFields, 0, 3);
                    $tableData['@order'] = implode(',', $orderFields);
                }
                
                $query[$tableName] = $tableData;
            }
        }

        return $query;
    }

    /**
     * 获取索引字段
     * @param string $tableName
     * @return array
     */
    protected function getIndexFields(string $tableName): array
    {
        return $this->indexConfig[$tableName] ?? ['id', 'user_id', 'create_time'];
    }

    /**
     * 生成缓存键
     * @param array $query
     * @return string
     */
    public function generateCacheKey(array $query): string
    {
        $key = md5(json_encode($query));
        return "apijson:{$key}";
    }

    /**
     * 检查是否需要缓存
     * @param array $query
     * @return bool
     */
    public function shouldCache(array $query): bool
    {
        // 只缓存 GET 请求
        foreach ($query as $tableName => $tableData) {
            if (isset($tableData['@insert']) || isset($tableData['@update']) || isset($tableData['@replace'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取缓存配置
     * @param array $query
     * @return array
     */
    public function getCacheConfig(array $query): array
    {
        $config = $this->cacheConfig;
        
        // 根据查询复杂度调整缓存时间
        $complexity = $this->calculateComplexity($query);
        
        if ($complexity > 10) {
            $config['ttl'] = 300; // 5分钟
        } elseif ($complexity > 5) {
            $config['ttl'] = 600; // 10分钟
        } else {
            $config['ttl'] = 1800; // 30分钟
        }
        
        return $config;
    }

    /**
     * 计算查询复杂度
     * @param array $query
     * @return int
     */
    protected function calculateComplexity(array $query): int
    {
        $complexity = 0;
        
        foreach ($query as $tableName => $tableData) {
            // 表数量
            $complexity += 1;
            
            // 条件数量
            $conditionCount = 0;
            foreach ($tableData as $field => $value) {
                if (strpos($field, '@') !== 0) {
                    $conditionCount++;
                }
            }
            $complexity += $conditionCount;
            
            // 嵌套查询
            if (substr($tableName, -2) === '[]') {
                $complexity += 2;
            }
            
            // 特殊语法
            if (isset($tableData['@insert']) || isset($tableData['@update']) || isset($tableData['@replace'])) {
                $complexity += 3;
            }
        }
        
        return $complexity;
    }

    /**
     * 设置缓存配置
     * @param array $config
     */
    public function setCacheConfig(array $config): void
    {
        $this->cacheConfig = $config;
    }

    /**
     * 设置索引配置
     * @param array $config
     */
    public function setIndexConfig(array $config): void
    {
        $this->indexConfig = $config;
    }
} 