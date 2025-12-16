<?php

namespace Imee\Comp\Nocode\Apijson\Parse;

use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Comp\Nocode\Apijson\Method\AbstractMethod;
use Imee\Comp\Nocode\Apijson\Method\DeleteMethod;
use Imee\Comp\Nocode\Apijson\Method\GetMethod;
use Imee\Comp\Nocode\Apijson\Method\HeadMethod;
use Imee\Comp\Nocode\Apijson\Method\PostMethod;
use Imee\Comp\Nocode\Apijson\Method\PutMethod;
use Imee\Comp\Nocode\Apijson\Method\ReplaceMethod;
use Imee\Comp\Nocode\Apijson\Utils\Logger;

class Parse
{
    protected $tagColumn = [
        'tag'   => null,
        'debug' => false,
        'other' => []
    ];

    protected $globalKey = [
        'count' => null,
        'page'  => null,
    ];

    protected $supMethod = [
        GetMethod::class,
        HeadMethod::class,
        PostMethod::class,
        PutMethod::class,
        DeleteMethod::class,
        ReplaceMethod::class
    ];

    protected $json;
    protected $method;
    protected $tag;

    protected $tableEntities = [];

    protected $querySql;

    public function __construct(array $json, string $method = 'GET', string $tag = '')
    {
        $this->json = $json;
        $this->method = $method;
        $this->tag = $tag;
        
        // 设置APIJSON调试日志路径
        $logDir = ROOT . DS . 'cache' . DS . 'log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        ini_set('log_errors', 1);
        ini_set('error_log', $logDir . DS . 'apijson_debug.log');
    }

    public function handle(bool $isQueryMany = false, array $extendData = []): array
    {
        // 新增：如有嵌套，优先递归处理
        if ($this->hasNested($this->json)) {
            return $this->parseNode($this->json, $extendData);
        }
        
        // 新增：自动先查被路径引用的表
        $orderedJson = $this->sortByReference($this->json);
        $this->json = $orderedJson;
        Logger::info("Parse handle - Ordered tables: " . implode(', ', array_keys($orderedJson)));
        
        if (empty($extendData)) {
            $extendData = $this->json; //引入原json
        }
        $result = [];

        // 先处理主表（只处理非[]结尾表）
        foreach ($this->json as $tableName => $condition) {
            Logger::info("Parse handle - Processing tableName: {$tableName}, method: {$this->method}");
            if (substr($tableName, -2) === '[]' && $tableName !== '[]') {
                Logger::info("Parse handle - Skipping array table: {$tableName}");
                continue; // 跳过子表，但不跳过 [] 本身
            }
            if (is_null($condition) || in_array($tableName, $this->filterKey()) || in_array($tableName, $this->globalKey())) {
                Logger::info("Parse handle - Skipping filtered/global table: {$tableName}");
                continue;
            }
            if ($tableName == '[]' && $this->method == 'GET') {
                Logger::info("Parse handle - Processing [] array query");
                $result[$tableName] = $this->handleArray($condition, array_merge($result, $extendData));
                continue;
            }
            if (!preg_match("/^[A-Z].+/", $tableName) || !is_array($condition)) {
                Logger::info("Parse handle - Skipping invalid table format: {$tableName}");
                continue; //不满足表名规范 跳出不往下执行
            }
            $curExtendData = array_merge($extendData, $result);
            $this->tableEntities[$tableName] = new TableEntity($tableName, $condition, $this->getGlobalArgs(), $curExtendData);
            foreach ($this->supMethod as $methodClass) {
                /** @var AbstractMethod $method */
                $method = new $methodClass($this->tableEntities[$tableName], $this->method);
                $method->setQueryMany(false);
                $method->setArrayQuery(false);
                $response = $method->handle();
                if (!is_null($response)) {
                    $result[$tableName] = $response;
                    if ($this->tagColumn['debug']) {
                        $result['debug'][] = $method->getQuery()->toSql();
                    }
                    break;
                }
            }
        }
        
        // 再处理有引用的主表（重新处理，此时extendData包含所有主表结果）
        foreach ($this->json as $tableName => $condition) {
            if (substr($tableName, -2) === '[]') continue; // 跳过子表
            if (is_null($condition) || in_array($tableName, $this->filterKey()) || in_array($tableName, $this->globalKey())) {
                continue;
            }
            if ($tableName == '[]' && $this->method == 'GET') {
                continue;
            }
            if (!preg_match("/^[A-Z].+/", $tableName) || !is_array($condition)) {
                continue;
            }
            
            // 检查是否有引用
            $hasReference = false;
            foreach ($condition as $key => $value) {
                if (substr($key, -1) === '@' && is_string($value)) {
                    $hasReference = true;
                    break;
                }
            }
            
            // 如果有引用且还没有结果，重新处理
            if ($hasReference && !isset($result[$tableName])) {
                $curExtendData = array_merge($extendData, $result);
                $this->tableEntities[$tableName] = new TableEntity($tableName, $condition, $this->getGlobalArgs(), $curExtendData);
                foreach ($this->supMethod as $methodClass) {
                    /** @var AbstractMethod $method */
                    $method = new $methodClass($this->tableEntities[$tableName], $this->method);
                    $method->setQueryMany(false);
                    $method->setArrayQuery(false);
                    $response = $method->handle();
                    if (!is_null($response)) {
                        $result[$tableName] = $response;
                        if ($this->tagColumn['debug']) {
                            $result['debug'][] = $method->getQuery()->toSql();
                        }
                        break;
                    }
                }
            }
        }
        // 再处理所有子表，递归传递主表结果（只处理[]结尾表）
        foreach ($this->json as $tableName => $condition) {
            if (substr($tableName, -2) !== '[]') continue;
            if (is_null($condition) || in_array($tableName, $this->filterKey()) || in_array($tableName, $this->globalKey())) {
                continue;
            }
            if (!preg_match("/^[A-Z].+/", $tableName) || !is_array($condition)) {
                continue;
            }
            // 拆分普通条件与嵌套子表，避免子表键进入 WHERE
            $nestedQueries = [];
            $normalConditions = [];
            foreach ($condition as $k => $v) {
                if (is_array($v) && preg_match('/^[A-Z].+/', $k)) {
                    $nestedQueries[$k] = $v;
                } else {
                    $normalConditions[$k] = $v;
                }
            }
            // 传入全局/主表扩展数据
            $mainResult = $result;
            foreach ($result as $k => $v) {
                if (substr($k, -2) !== '[]') {
                    $mainResult[$k . '[]'] = is_array($v) ? $v : [$v];
                }
            }
            $this->tableEntities[$tableName] = new TableEntity($tableName, $normalConditions, $this->getGlobalArgs(), $mainResult);
            // 应用关联查询 Limit 优化：当引用字段为主键/唯一索引且未显式设置 @limit 时，移除默认 limit 限制
            $this->applyLimitOptimization($this->tableEntities[$tableName]);
            foreach ($this->supMethod as $methodClass) {
                /** @var AbstractMethod $method */
                $method = new $methodClass($this->tableEntities[$tableName], $this->method);
                $method->setQueryMany(true);
                $method->setArrayQuery(false);
                try {
                    $response = $method->handle();
                    // 调试信息：记录子表处理结果
                    error_log("Parse handle - Table: {$tableName}, Response: " . (is_null($response) ? 'null' : 'not null'));
                    if (!is_null($response)) {
                        // 对每一条记录递归处理拆分出的嵌套子表
                        if (!empty($nestedQueries) && is_array($response) && isset($response[0])) {
                            foreach ($response as $idx => $row) {
                                if (is_array($row)) {
                                    // 为兄弟/子表引用补充 k[] 键
                                    $extendRow = $row;
                                    foreach ($row as $rk => $rv) {
                                        if (substr($rk, -2) !== '[]') {
                                            $extendRow[$rk . '[]'] = is_array($rv) ? $rv : [$rv];
                                        }
                                    }
                                    $processed = $this->processNestedQueries($extendRow, $nestedQueries, $tableName);
                                    $response[$idx] = $this->removeArraySuffixKeys($processed);
                                }
                            }
                        }
                        error_log("Parse handle - Table: {$tableName}, Response data: " . json_encode($response));
                        $result[$tableName] = $response;
                        if ($this->tagColumn['debug']) {
                            $result['debug'][] = $method->getQuery()->toSql();
                        }
                        break;
                    } else {
                        error_log("Parse handle - Table: {$tableName}, Response is null, trying next method");
                    }
                } catch (\Exception $e) {
                    error_log("Parse handle - Table: {$tableName}, Exception: " . $e->getMessage());
                    error_log("Parse handle - Table: {$tableName}, Exception trace: " . $e->getTraceAsString());
                    throw $e;
                }
            }
            
            // 如果所有方法都返回 null，根据表类型设置默认值
            if (!isset($result[$tableName])) {
                if (substr($tableName, -2) === '[]') {
                    // [] 结尾的表返回空数组
                    $result[$tableName] = [];
                } else {
                    // 普通表返回 null（可选，根据官方规范）
                    // $result[$tableName] = null;
                }
            }
        }
        return $this->resultExtendHandle($result);
    }

    protected function resultExtendHandle(array $result): array
    {
        if ($this->tagColumn) {

        }
        return $result;
    }

    /**
     * 处理[]的数据
     * @param array $jsonData
     * @param array $extendData
     * @return array
     */
    protected function handleArray(array $jsonData, array $extendData = []): array
    {
        $result = [];
        
        // 对数组查询中的表也进行排序
        $orderedJsonData = $this->sortByReference($jsonData);
        Logger::info("Parse handleArray - Ordered tables: " . implode(', ', array_keys($orderedJsonData)));

        // 首先处理没有引用关系的表（通常是主表）
        $mainTableProcessed = false;
        foreach ($orderedJsonData as $tableName => $condition) {
            if (is_null($condition)) {
                continue;
            }
            //TODO db前缀处理
            if (!preg_match("/^[A-Z].+/", $tableName) || !is_array($condition)) {
                continue;
            }
            
            // 调试信息：记录数组查询处理
            Logger::info("Parse handleArray - Processing table: {$tableName}");
            
            // 分离嵌套查询和普通查询条件
            $nestedQueries = [];
            $normalConditions = [];
            
            foreach ($condition as $key => $value) {
                if (is_array($value) && preg_match("/^[A-Z].+/", $key)) {
                    // 这是一个嵌套查询
                    $nestedQueries[$key] = $value;
                } else {
                    // 这是普通查询条件
                    $normalConditions[$key] = $value;
                }
            }
            
            Logger::info("Parse handleArray - Table {$tableName} - normalConditions: " . json_encode($normalConditions));
            Logger::info("Parse handleArray - Table {$tableName} - nestedQueries: " . json_encode($nestedQueries));
            
            // 检查是否有引用关系
            $hasReference = false;
            foreach ($normalConditions as $key => $value) {
                if (substr($key, -1) === '@' && is_string($value)) {
                    $hasReference = true;
                    break;
                }
            }
            
            Logger::info("Parse handleArray - Table {$tableName} - hasReference: " . ($hasReference ? 'true' : 'false'));
            Logger::info("Parse handleArray - Table {$tableName} - mainTableProcessed: " . ($mainTableProcessed ? 'true' : 'false'));
            
            if (!$hasReference && !$mainTableProcessed) {
                // 处理主表（没有引用关系的表）
                Logger::info("Parse handleArray - Processing main table: {$tableName}");
                $this->tableEntities['[]'][$tableName] = new TableEntity($tableName, $normalConditions, $this->getGlobalArgs(), []);
                $method = new GetMethod($this->tableEntities['[]'][$tableName], $this->method);
                $method->setQueryMany(true);
                $method->setArrayQuery(true);
                $response = $method->handle();
                
                if (!is_null($response)) {
                    // 处理嵌套查询
                    if (!empty($nestedQueries)) {
                        Logger::info("Parse handleArray - Processing nested queries for table {$tableName}");
                        
                        // 为每个子表记录处理嵌套查询
                        if (is_array($response)) {
                            foreach ($response as $subIndex => $subRecord) {
                                $response[$subIndex] = $this->processNestedQueries($subRecord, $nestedQueries, $tableName);
                            }
                        }
                    }
                    
                    // 将主表数据转换为标准格式
                    if (is_array($response)) {
                        foreach ($response as $index => $record) {
                            $result[$index] = [$tableName => $record];
                        }
                    }
                    
                    $mainTableProcessed = true;
                    Logger::info("Parse handleArray - Main table {$tableName}: " . json_encode($response));
                } else {
                    $result = [];
                    Logger::warning("Parse handleArray - Main table {$tableName}: empty result");
                }
            } elseif ($hasReference) {
                // 处理有引用关系的表
                Logger::info("Parse handleArray - Table {$tableName} has reference, processing for each main record");
                
                // 为每个主表记录查询对应的子表数据
                foreach ($result as $recordIndex => $item) {
                    Logger::info("Parse handleArray - Processing record {$recordIndex} for table {$tableName}");
                    
                    // 构建子表查询条件，包含主表引用
                    $subCondition = $normalConditions;
                    $refValues = [];
                    
                    foreach ($subCondition as $subKey => $subValue) {
                        if (substr($subKey, -1) === '@' && is_string($subValue)) {
                            // 解析引用路径
                            $refParts = explode('/', $subValue);
                            $refTable = $refParts[0];
                            $refField = $refParts[1];
                            
                            // 查找引用表中的数据
                            $refValues = [];
                            if (isset($item[$refTable])) {
                                // 如果引用表在当前记录中
                                $refTableData = $item[$refTable];
                                
                                // 检查引用表是否返回数组
                                if (is_array($refTableData) && isset($refTableData[0]) && is_array($refTableData[0])) {
                                    // 数组格式：[{module_id: 2470}, {module_id: 2471}, ...]
                                    $refValues = array_column($refTableData, $refField);
                                    Logger::info("Parse handleArray - Extracted array values from {$refTable}: " . json_encode($refValues));
                                } else {
                                    // 单条记录格式：{module_id: 2470}
                                    $refValue = $refTableData[$refField] ?? null;
                                    if ($refValue !== null) {
                                        $refValues = [$refValue];
                                    }
                                    Logger::info("Parse handleArray - Extracted single value from {$refTable}: " . json_encode($refValues));
                                }
                            } else {
                                // 尝试查找带[]后缀的表名
                                $refTableWithArray = $refTable . '[]';
                                if (isset($item[$refTableWithArray])) {
                                    $refTableData = $item[$refTableWithArray];
                                    
                                    // 检查引用表是否返回数组
                                    if (is_array($refTableData) && isset($refTableData[0]) && is_array($refTableData[0])) {
                                        // 数组格式：[{module_id: 2470}, {module_id: 2471}, ...]
                                        $refValues = array_column($refTableData, $refField);
                                        Logger::info("Parse handleArray - Extracted array values from {$refTableWithArray}: " . json_encode($refValues));
                                    } else {
                                        // 单条记录格式：{module_id: 2470}
                                        $refValue = $refTableData[$refField] ?? null;
                                        if ($refValue !== null) {
                                            $refValues = [$refValue];
                                        }
                                        Logger::info("Parse handleArray - Extracted single value from {$refTableWithArray}: " . json_encode($refValues));
                                    }
                                }
                            }
                            
                            // 调试信息：记录引用解析过程
                            Logger::info("Parse handleArray - Processing reference {$subKey} = {$subValue}");
                            Logger::info("Parse handleArray - refTable: {$refTable}, refField: {$refField}");
                            Logger::info("Parse handleArray - item keys: " . implode(', ', array_keys($item)));
                            if (isset($item[$refTable])) {
                                Logger::info("Parse handleArray - item[{$refTable}] keys: " . implode(', ', array_keys($item[$refTable])));
                            }
                            
                            if (!empty($refValues)) {
                                // 如果有多个值，使用IN查询
                                if (count($refValues) > 1) {
                                    $subCondition[substr($subKey, 0, -1) . '{}'] = $refValues;
                                    Logger::info("Parse handleArray - Converted to IN query: " . substr($subKey, 0, -1) . '{} = ' . json_encode($refValues));
                                } else {
                                    $subCondition[substr($subKey, 0, -1)] = $refValues[0];
                                    Logger::info("Parse handleArray - Set single value: " . substr($subKey, 0, -1) . ' = ' . json_encode($refValues[0]));
                                }
                                unset($subCondition[$subKey]);
                                Logger::info("Parse handleArray - Resolved reference {$subKey} to " . json_encode($refValues) . " for table {$tableName}");
                            } else {
                                Logger::warning("Parse handleArray - Could not resolve reference {$subKey} for table {$tableName}");
                            }
                        }
                    }
                    
                    // 检查是否为聚合查询
                    $isAggregateQuery = false;
                    foreach ($subCondition as $condKey => $condValue) {
                        if (in_array($condKey, ['@group', '@sum', '@count', '@avg', '@max', '@min'])) {
                            $isAggregateQuery = true;
                            break;
                        }
                    }
                    
                    // 执行子表查询
                    Logger::info("Parse handleArray - Executing query for table {$tableName} with condition: " . json_encode($subCondition));
                    Logger::info("Parse handleArray - Creating TableEntity with tableName: '{$tableName}'");
                    $this->tableEntities['[]'][$tableName] = new TableEntity($tableName, $subCondition, $this->getGlobalArgs(), []);
                    
                    // 手动应用 FunctionLimitHandle 的优化逻辑
                    $this->applyLimitOptimization($this->tableEntities['[]'][$tableName]);
                    
                    $method = new GetMethod($this->tableEntities['[]'][$tableName], $this->method);
                    $isArrayTable = substr($tableName, -2) === '[]';
                    $method->setQueryMany($isArrayTable);
                    $method->setArrayQuery(true);
                    $response = $method->handle();
                    
                    Logger::info("Parse handleArray - Query response for table {$tableName}: " . json_encode($response));
                    
                    if (!is_null($response)) {
                        // 处理嵌套查询
                        if (!empty($nestedQueries)) {
                            Logger::info("Parse handleArray - Processing nested queries for table {$tableName}");
                            // 数组表：逐条处理；单对象表：直接处理对象
                            if ($isArrayTable && is_array($response) && isset($response[0])) {
                                foreach ($response as $subIndex => $subRecord) {
                                    if (is_array($subRecord)) {
                                        $response[$subIndex] = $this->processNestedQueries($subRecord, $nestedQueries, $tableName);
                                    }
                                }
                            } elseif (is_array($response)) {
                                $response = $this->processNestedQueries($response, $nestedQueries, $tableName);
                            }
                        }
                        
                        // 如果是聚合查询，确保返回格式正确
                        if ($isAggregateQuery) {
                            // 聚合查询应该返回单个汇总对象，而不是数组
                            if (is_array($response) && count($response) === 1) {
                                $result[$recordIndex][$tableName] = $response[0];
                                Logger::info("Parse handleArray - Aggregate query for table {$tableName}, returning single object: " . json_encode($response[0]));
                            } else {
                                $result[$recordIndex][$tableName] = $response;
                                Logger::info("Parse handleArray - Aggregate query for table {$tableName}, returning array: " . json_encode($response));
                            }
                        } else {
                            $result[$recordIndex][$tableName] = $response;
                            Logger::info("Parse handleArray - Sub table {$tableName} for record {$recordIndex}: " . json_encode($response));
                        }
                    } else {
                        // 空结果：按官方定义，非 [] 表返回 null，带 [] 表返回 []
                        $result[$recordIndex][$tableName] = $isArrayTable ? [] : null;
                        Logger::warning("Parse handleArray - Sub table {$tableName} for record {$recordIndex}: empty result");
                    }
                }
                
                // 不再做二次清理，确保已按记录索引准确写入
            }
        }
        
        return $result;
    }
    
    /**
     * 处理嵌套查询
     * @param array $record 当前记录
     * @param array $nestedQueries 嵌套查询配置
     * @param string $parentTableName 父表名
     * @return array
     */
    private function processNestedQueries(array $record, array $nestedQueries, string $parentTableName): array
    {
        // 优先处理不依赖兄弟表的（相对引用或无引用），再处理依赖兄弟表的，保证依赖顺序
        $orderedNested = [];
        $independent = [];
        $dependent = [];
        foreach ($nestedQueries as $k => $v) {
            $hasExternalRef = false;
            foreach ($v as $condKey => $condVal) {
                if (substr($condKey, -1) === '@' && is_string($condVal) && strpos($condVal, '/') !== 0) {
                    $hasExternalRef = true; // 例如 "CmsModuleUser/module_id"
                    break;
                }
            }
            if ($hasExternalRef) {
                $dependent[$k] = $v;
            } else {
                $independent[$k] = $v;
            }
        }
        $orderedNested = $independent + $dependent; // 保持原相对顺序

        foreach ($orderedNested as $nestedTableName => $nestedCondition) {
            Logger::info("Parse processNestedQueries - Processing nested table {$nestedTableName} for parent {$parentTableName}");
            
            // 处理嵌套查询中的引用关系
            $processedCondition = $nestedCondition;
            foreach ($processedCondition as $key => $value) {
                if (substr($key, -1) === '@' && is_string($value)) {
                    // 解析引用路径
                    $refParts = explode('/', $value);
                    $refTable = $refParts[0];
                    $refField = $refParts[1];
                    
                    if ($refTable === '' && $refField) {
                        // 相对引用，引用父表记录中的字段
                        $refValue = $record[$refField] ?? null;
                        if ($refValue !== null) {
                            $processedCondition[substr($key, 0, -1)] = $refValue;
                            unset($processedCondition[$key]);
                            Logger::info("Parse processNestedQueries - Resolved relative reference /{$refField} to {$refValue} for table {$nestedTableName}");
                        } else {
                            Logger::warning("Parse processNestedQueries - Could not resolve relative reference /{$refField} for table {$nestedTableName}");
                        }
                    }
                }
            }
            // 将更深层的嵌套子表从条件中分离出来
            $childNestedQueries = [];
            $normalConditions = [];
            foreach ($processedCondition as $k => $v) {
                if (is_array($v) && preg_match('/^[A-Z].+/', $k)) {
                    $childNestedQueries[$k] = $v; // 深层嵌套子表
                } else {
                    $normalConditions[$k] = $v; // 普通条件
                }
            }

            // 执行嵌套查询（仅用普通条件）。传入当前记录作为 extendData，用于 QuoteReplace 解析跨兄弟表引用
            Logger::info("Parse processNestedQueries - Creating TableEntity with nestedTableName: '{$nestedTableName}'");
            $extendData = $record;
            // 同步提供 k[] 形式，便于通用解析
            foreach ($record as $k => $v) {
                if (substr($k, -2) !== '[]') {
                    $extendData[$k . '[]'] = is_array($v) ? $v : [$v];
                }
            }
            $this->tableEntities['[]'][$nestedTableName] = new TableEntity($nestedTableName, $normalConditions, $this->getGlobalArgs(), $extendData);
            // 应用关联查询 Limit 优化（如引用字段为主键/唯一索引）
            $this->applyLimitOptimization($this->tableEntities['[]'][$nestedTableName]);
            $method = new GetMethod($this->tableEntities['[]'][$nestedTableName], $this->method);
            $nestedIsArray = substr($nestedTableName, -2) === '[]';
            $method->setQueryMany($nestedIsArray);
            $method->setArrayQuery(true);
            $response = $method->handle();
            
            if (!is_null($response)) {
                // 如果还有更深层的嵌套子表，递归处理
                if (!empty($childNestedQueries)) {
                    if ($nestedIsArray && is_array($response) && isset($response[0])) {
                        foreach ($response as $idx => $row) {
                            if (is_array($row)) {
                                $response[$idx] = $this->processNestedQueries($row, $childNestedQueries, $nestedTableName);
                            }
                        }
                    } elseif (is_array($response)) {
                        $response = $this->processNestedQueries($response, $childNestedQueries, $nestedTableName);
                    }
                }

                $record[$nestedTableName] = $response;
                Logger::info("Parse processNestedQueries - Nested table {$nestedTableName}: " . json_encode($response));
            } else {
                // 空结果：非 [] 返回 null，[] 返回 []
                $record[$nestedTableName] = $nestedIsArray ? [] : null;
                Logger::warning("Parse processNestedQueries - Nested table {$nestedTableName}: empty result");
            }
        }
        
        return $record;
    }

    // 清理临时注入的 xxx[] 键
    private function removeArraySuffixKeys(array $row): array
    {
        foreach (array_keys($row) as $key) {
            if (substr($key, -2) === '[]') {
                $base = substr($key, 0, -2);
                // 仅清理字段类的临时键（如 id[]/uid[]/...），保留表名（如 CmsModuleUser[]）
                if (!preg_match('/^[A-Z]/', $base)) {
                    unset($row[$key]);
                }
            }
        }
        return $row;
    }

    protected function filterKey(): array
    {
        return array_keys($this->tagColumn);
    }

    protected function getGlobalArgs(): array
    {
        return array_filter($this->globalKey);
    }

    protected function globalKey(): array
    {
        return array_keys($this->globalKey);
    }

    // 新增：判断是否有嵌套子表
    private function hasNested($json): bool
    {
        Logger::info("Parse hasNested - Checking JSON: " . json_encode($json));
        foreach ($json as $tableName => $condition) {
            if (is_array($condition)) {
                foreach ($condition as $k => $v) {
                    if (substr($k, -2) === '[]' && is_array($v)) {
                        Logger::info("Parse hasNested - Found nested table: {$k}");
                        return true;
                    }
                }
            }
        }
        Logger::info("Parse hasNested - No nested tables found");
        return false;
    }

    // 新增：递归解析嵌套结构
    private function parseNode($json, $extendData = [])
    {
        Logger::info("Parse parseNode - Called with JSON: " . json_encode($json));
        $result = [];
        
        // 如果传入的是 {"[]": {...}} 格式，需要特殊处理
        if (isset($json['[]']) && is_array($json['[]'])) {
            Logger::info("Parse parseNode - Detected array query format, processing inner tables");
            return $this->handleArray($json['[]'], $extendData);
        }
        
        foreach ($json as $tableName => $condition) {
            if (str_ends_with($tableName, '[]')) {
                // 0. 拆分普通条件与嵌套子表
                $nestedQueries = [];
                $normalConditions = [];
                if (is_array($condition)) {
                    foreach ($condition as $k => $v) {
                        if (is_array($v) && preg_match('/^[A-Z].+/', $k)) {
                            $nestedQueries[$k] = $v; // 深/浅层嵌套，支持 Xxx 与 Xxx[]
                        } else {
                            $normalConditions[$k] = $v;
                        }
                    }
                }

                // 1. 查主表（列表）
                Logger::info("Parse parseNode - Creating TableEntity with tableName: '{$tableName}' (array table)");
                $tableEntity = new TableEntity($tableName, $normalConditions, $this->getGlobalArgs(), $extendData);
                // 应用关联查询 Limit 优化（如引用字段为主键/唯一索引）
                $this->applyLimitOptimization($tableEntity);
                $query = new GetMethod($tableEntity, 'GET');
                $rows = $query->handle();

                // 2. 对每一行递归处理（支持非 [] 子表、多层嵌套）
                if (is_array($rows)) {
                    foreach ($rows as &$row) {
                        if (!empty($nestedQueries) && is_array($row)) {
                            // 为兄弟查询提前提供 k 与 k[] 两种键，便于 QuoteReplace 在数组上下文取值
                            $extendRow = $row;
                            foreach ($row as $rk => $rv) {
                                if (substr($rk, -2) !== '[]') {
                                    $extendRow[$rk . '[]'] = is_array($rv) ? $rv : [$rv];
                                }
                            }
                            $processed = $this->processNestedQueries($extendRow, $nestedQueries, $tableName);
                            // 移除临时注入的 [] 键，避免污染结果
                            $row = $this->removeArraySuffixKeys($processed);
                        }
                    }
                }

                $result[$tableName] = $rows;
            } else if (is_array($condition)) {
                // 1. 拆分普通条件与嵌套子表，避免子表键进入主表 WHERE
                $nestedQueries = [];
                $normalConditions = [];
                foreach ($condition as $k => $v) {
                    if (is_array($v) && preg_match('/^[A-Z].+/', $k)) {
                        // 这是一个嵌套子表，例如 CmsModuleUser 或 CmsModuleUser[]
                        $nestedQueries[$k] = $v;
                    } else {
                        // 普通查询条件与 @ 指令
                        $normalConditions[$k] = $v;
                    }
                }

                // 2. 查主表（单对象）
                Logger::info("Parse parseNode - Creating TableEntity with tableName: '{$tableName}' (single table)");
                $tableEntity = new TableEntity($tableName, $normalConditions, $this->getGlobalArgs(), $extendData);
                $query = new GetMethod($tableEntity, 'GET');
                $row = $query->handle(); // 查详情时返回对象或 null

                // 3. 递归处理嵌套子表（支持 Xxx 与 Xxx[]）
                if (is_array($row)) {
                    foreach ($nestedQueries as $nestedTableName => $nestedCondition) {
                        $parsed = $this->parseNode([$nestedTableName => $nestedCondition], $row);
                        $row[$nestedTableName] = $parsed[$nestedTableName] ?? (str_ends_with($nestedTableName, '[]') ? [] : null);
                    }
                }

                $result[$tableName] = $row;
            }
        }
        return $result;
    }

    // 新增：根据路径引用自动排序，先查被引用表
    private function sortByReference($json)
    {
        $refs = [];
        $tableDependencies = [];
        
        // 分析每个表的依赖关系
        foreach ($json as $tableName => $condition) {
            if (is_array($condition)) {
                $tableDependencies[$tableName] = [];
                foreach ($condition as $k => $v) {
                    if (substr($k, -1) === '@' && is_string($v)) {
                        // 解析引用路径，支持多种格式
                        if (preg_match('#^/?([A-Za-z0-9_]+)(\[\])?/(.+)$#', $v, $m)) {
                            $refTable = $m[1];
                            $tableDependencies[$tableName][] = $refTable;
                            Logger::info("Parse sortByReference - Table {$tableName} depends on {$refTable} (from {$v})");
                        }
                    }
                }
            }
        }
        
        // 拓扑排序
        $visited = [];
        $result = [];
        
        $visit = function($table) use (&$visit, &$visited, &$result, $tableDependencies, $json) {
            if (isset($visited[$table])) return;
            $visited[$table] = true;
            
            // 先处理依赖的表
            foreach (($tableDependencies[$table] ?? []) as $dep) {
                if (isset($json[$dep])) {
                    $visit($dep);
                }
            }
            
            // 然后添加当前表
            $result[$table] = $json[$table];
            Logger::info("Parse sortByReference - Added table {$table} to result");
        };
        
        // 处理所有表
        foreach (array_keys($json) as $table) {
            $visit($table);
        }
        
        Logger::info("Parse sortByReference - Final order: " . implode(' -> ', array_keys($result)));
        return $result;
    }

    // 新增：手动应用 FunctionLimitHandle 的优化逻辑
    private function applyLimitOptimization(TableEntity $tableEntity)
    {
        $conditionEntity = $tableEntity->getConditionEntity();
        $condition = $conditionEntity->getCondition();
        $tableName = $tableEntity->getTableName();
        
        Logger::info("Parse applyLimitOptimization - 开始检查表: {$tableName}");
        Logger::info("Parse applyLimitOptimization - 条件: " . json_encode($condition));
        
        // 检查是否有引用查询（包括已解析的引用）
        $hasReference = false;
        $referenceField = '';
        
        foreach ($condition as $key => $value) {
            // 检查原始引用格式：field@
            if (substr($key, -1) === '@' && is_string($value)) {
                $hasReference = true;
                $referenceField = substr($key, 0, -1); // 去掉 @ 后缀
                Logger::info("Parse applyLimitOptimization - 发现原始引用: {$key} = {$value}, 字段: {$referenceField}");
                break;
            }
            // 检查已解析的引用格式：field{}（IN 查询）
            elseif (substr($key, -2) === '{}' && is_array($value)) {
                $hasReference = true;
                $referenceField = substr($key, 0, -2); // 去掉 {} 后缀
                Logger::info("Parse applyLimitOptimization - 发现已解析引用: {$key} = " . json_encode($value) . ", 字段: {$referenceField}");
                break;
            }
        }
        
        if (!$hasReference) {
            Logger::info("Parse applyLimitOptimization - 表 {$tableName} 没有引用查询，跳过优化");
            return; // 没有引用查询，不需要优化
        }
        
        Logger::info("Parse applyLimitOptimization - 表 {$tableName} 有引用查询，继续检查");
        
        // 静态判定（内部自取 table_config）
        if (TableEntity::isPrimaryKeyOrUniqueIndexFromConfig($tableName, $referenceField)) {
            // 如果是主键或唯一索引，移除默认的 limit 限制
            $conditionEntity->setLimit(0); // 设置为0表示无限制
            Logger::info("Parse applyLimitOptimization - 优化生效：为表 {$tableName} 的字段 {$referenceField} 移除 limit 限制");
        } else {
            Logger::info("Parse applyLimitOptimization - 字段 {$referenceField} 不是主键或唯一索引，不进行优化");
            // 兜底：若为 IN 数组引用，且未显式设置 @limit，则将 limit 提升为引用值数量，避免默认 10 条截断
            if (isset($condition[$referenceField . '{}']) && is_array($condition[$referenceField . '{}'])) {
                $values = $condition[$referenceField . '{}'];
                $countValues = count($values);
                if ($countValues > 0 && ($conditionEntity->getLimit() === 10)) { // 默认 10 表示未设置
                    // 将 limit 提升为引用数量（受 ConditionEntity 上限 1000 约束）
                    $newLimit = min($countValues, 1000);
                    $conditionEntity->setLimit($newLimit);
                    Logger::info("Parse applyLimitOptimization - 兜底：将 limit 提升为引用数量 {$newLimit}");
                }
            }
        }
    }
    
    /**
     * 检查字段是否是主键或唯一索引
     * @param string $fieldName
     * @param array $tableConfig
     * @return bool
     */
    // 判定逻辑已统一迁移至 TableEntity::isPrimaryKeyOrUniqueIndexFromConfig

}