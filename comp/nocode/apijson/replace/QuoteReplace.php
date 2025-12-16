<?php

namespace Imee\Comp\Nocode\Apijson\Replace;

use Imee\Comp\Nocode\Apijson\Utils\Logger;

class QuoteReplace extends AbstractReplace
{
    public function __construct($condition)
    {
        parent::__construct($condition);
        
        // 设置APIJSON调试日志路径
        $logDir = ROOT . DS . 'cache' . DS . 'log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        ini_set('log_errors', 1);
        ini_set('error_log', $logDir . DS . 'apijson_debug.log');
    }
    
    protected function process()
    {
        $condition = $this->condition->getCondition();

        foreach (array_filter($condition, function($key){
            return substr($key, -1) === '@';
        }, ARRAY_FILTER_USE_KEY) as $key => $value)
        {
            if (!is_string($value)) {
                continue;
            }
            // 新增：支持批量路径引用
            $rawPath = $value;
            $extendData = $this->condition->getExtendData();
            $newKey = substr($key, 0, strlen($key) - 1);
            $resolved = null;
            
            // 支持 /TableName/field 或 /TableName[]/field 批量提取
            if (preg_match('#^/?([A-Za-z0-9_]+)(\[\])?/(.+)$#', $rawPath, $m)) {
                $tableName = $m[1];
                $field = $m[3];
                
                // 调试信息：记录引用处理
                Logger::info("QuoteReplace - Table: {$tableName}, Field: {$field}, ExtendData keys: " . implode(',', array_keys($extendData)));
                
                // 尝试多种可能的键名
                $possibleKeys = [
                    $tableName . '[]',  // CmsModuleUser[]
                    $tableName,         // CmsModuleUser
                ];
                
                foreach ($possibleKeys as $tableKey) {
                    if (isset($extendData[$tableKey])) {
                        $tableData = $extendData[$tableKey];
                        
                        if (is_array($tableData)) {
                            // 如果是数组，提取指定字段
                            if (isset($tableData[0]) && is_array($tableData[0])) {
                                // 数组格式：[{id: 1, user_id: 572}, ...]
                                $resolved = array_column($tableData, $field);
                                // 调试信息：记录数组字段提取
                                Logger::info("QuoteReplace - Extracted array field {$field}: " . json_encode($resolved));
                            } else {
                                // 单条记录格式：{id: 1, user_id: 572}
                                $resolved = isset($tableData[$field]) ? $tableData[$field] : null;
                                // 调试信息：记录单记录字段提取
                                Logger::info("QuoteReplace - Extracted single field {$field}: " . json_encode($resolved));
                            }
                        } else {
                            // 如果不是数组，可能是单个对象
                            $resolved = isset($tableData[$field]) ? $tableData[$field] : null;
                            // 调试信息：记录对象字段提取
                            Logger::info("QuoteReplace - Extracted object field {$field}: " . json_encode($resolved));
                        }
                        
                        if ($resolved !== null) {
                            Logger::info("QuoteReplace - Found data in {$tableKey}: " . json_encode($resolved));
                            break;
                        }
                    }
                }
            }
            
            // fallback: 单值
            if ($resolved === null) {
                // 兼容原有点号路径
                $path = str_replace(['/', '[]'], ['.', 'currentItem'], $value);
                $path = ltrim($path, '.');
                if (preg_match('/^[A-Z][a-zA-Z0-9_]*\.(.+)$/', $path, $m)) {
                    $path = $m[1];
                }
                $resolved = data_get($extendData, $path);
            }
            
            // 修复：处理数组引用查询
            if (is_array($resolved) && count($resolved) > 0) {
                // 如果引用值是数组，转换为IN查询
                $inKey = $newKey . '{}';
                $condition[$inKey] = $resolved;
                Logger::info("QuoteReplace - Converted array reference to IN query: {$inKey} = " . json_encode($resolved));
            } else {
                // 如果引用值是单个值，直接赋值
                $condition[$newKey] = $resolved;
                Logger::info("QuoteReplace - Set single value reference: {$newKey} = " . json_encode($resolved));
            }
            
            unset($condition[$key]);
            $this->condition->setCondition($condition);
            
            // 调试信息：记录引用替换结果
            Logger::info("QuoteReplace - Replaced {$key} with " . (is_array($resolved) ? $newKey . '{}' : $newKey) . ": " . json_encode($resolved));
        }
    }
}

if (!function_exists('data_get')) {
    function data_get($array, $path, $default = null) {
        if (!is_array($array)) return $default;
        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }
}