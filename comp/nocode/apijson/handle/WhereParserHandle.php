<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;
use Imee\Comp\Nocode\Apijson\Utils\Logger;

class WhereParserHandle extends AbstractHandle
{
    protected $sql = '';
    protected $bindings = [];

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

    public function handle()
    {
        $condition = $this->condition->getCondition();
        //exit('[DEBUG] WhereParserHandle condition: ' . json_encode($condition));
        
        // 调试信息：记录查询条件
        Logger::info("WhereParserHandle - Condition: " . json_encode($condition));
        
        // 筛选出所有非 @- 开头的查询条件
        $whereConditions = [];
        foreach ($condition as $key => $value) {
            if (strpos($key, '@') !== 0) {
                $whereConditions[$key] = $value;
            } else {
                // 将 @ 操作符的条件也包含在内
                $whereConditions[$key] = $value;
            }
        }

        if (empty($whereConditions)) {
            Logger::info("WhereParserHandle - No where conditions found");
            return;
        }

        Logger::info("WhereParserHandle - Where conditions: " . json_encode($whereConditions));
        $result = $this->parse($whereConditions);
        Logger::info("WhereParserHandle - Parse result: " . json_encode($result));
        
        if (!empty($result['sql'])) {
            // 使用一个特殊的键名，确保不会与任何真实字段冲突
            $this->condition->addQueryWhere('@where_parser', $result['sql'], $result['bindings']);
        }

        // 不再 unset 字段，保留给其它 Handle 处理
    }

    private function parse(array $conditions, string $operator = 'AND'): array
    {
        $sqlParts = [];
        $bindings = [];

        foreach ($conditions as $key => $value) {
            // 递归处理逻辑分组 - APIJSON官方语法
            if ($key === '@' && is_array($value)) {
                $logicOperator = strtoupper($value['operator'] ?? 'AND');
                unset($value['operator']);
                $nestedResult = $this->parse($value, $logicOperator);
                if (!empty($nestedResult['sql'])) {
                    $sqlParts[] = "({$nestedResult['sql']})";
                    $bindings = array_merge($bindings, $nestedResult['bindings']);
                }
                continue;
            }

            // 处理嵌套的AND/OR逻辑 - 支持官方语法的嵌套结构
            if (in_array($key, ['AND', 'OR']) && is_array($value)) {
                $nestedResult = $this->parse($value, $key);
                if (!empty($nestedResult['sql'])) {
                    $sqlParts[] = "({$nestedResult['sql']})";
                    $bindings = array_merge($bindings, $nestedResult['bindings']);
                }
                continue;
            }

            // 解析 OR 查询： "key1|key2"
            if (strpos($key, '|') !== false) {
                $orKeys = explode('|', $key);
                $orParts = [];
                foreach ($orKeys as $orKey) {
                    $orParts[] = "`{$orKey}` = ?";
                }
                $sqlParts[] = '(' . implode(' OR ', $orParts) . ')';
                $bindings = array_merge($bindings, array_fill(0, count($orKeys), $value));
                continue;
            }
            
            // 解析操作符 - 修复正则表达式以正确处理复合操作符
            if (preg_match('/^([a-zA-Z_]\w*)([!$}{%~<>]*)$/', $key, $matches)) {
                // 修复复合操作符匹配
                $field = $matches[1];
                $op = $matches[2];
            } else {
                // 尝试手动解析
                if (preg_match('/^([a-zA-Z_]\w*)(.*)$/', $key, $matches)) {
                    $field = $matches[1];
                    $op = $matches[2];
                } else {
                    continue; // 跳过无法解析的键
                }
            }
            
            // 重新解析操作符以正确处理复合操作符
            if (strpos($key, '>=') !== false) {
                $op = '>=';
            } elseif (strpos($key, '<=') !== false) {
                $op = '<=';
            } elseif (strpos($key, '!=') !== false) {
                $op = '!=';
            } elseif (strpos($key, '!{}') !== false) {
                $op = '!{}';
            } elseif (strpos($key, '{}') !== false) {
                $op = '{}';
            } elseif (strpos($key, '>') !== false) {
                $op = '>';
            } elseif (strpos($key, '<') !== false) {
                $op = '<';
            } elseif (strpos($key, '$') !== false) {
                // 检查是否是 BETWEEN 查询（包含逗号）
                if (is_string($value) && strpos($value, ',') !== false) {
                    $op = 'BETWEEN';
                } else {
                    $op = '$';
                }
            } elseif (strpos($key, '^') !== false) {
                $op = '^';
            } elseif (strpos($key, '%') !== false) {
                $op = '%';
            }

            // 新增：如果值是数组且无操作符，自动转 IN
            if (empty($op) && is_array($value)) {
                if (count($value) === 0) {
                    $sqlParts[] = '1=0'; // 防止 IN () 语法错误
                } else {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $sqlParts[] = "`{$field}` IN ({$placeholders})";
                    $bindings = array_merge($bindings, $value);
                }
                continue;
            }
            
            // 处理复合操作符
            $actualOp = $op;
            if ($op === '>=' || $op === '<=' || $op === '!=') {
                $actualOp = $op;
            } elseif ($op === '>') {
                $actualOp = '>';
            } elseif ($op === '<') {
                $actualOp = '<';
            }
            
            switch ($actualOp) {
                case '{}': // IN
                    if (count($value) === 0) {
                        $sqlParts[] = '1=0';
                    } else {
                        $placeholders = implode(',', array_fill(0, count($value), '?'));
                        $sqlParts[] = "`{$field}` IN ({$placeholders})";
                        $bindings = array_merge($bindings, $value);
                    }
                    break;
                case '!{}': // NOT IN
                    if (count($value) === 0) {
                        $sqlParts[] = '1=1';
                    } else {
                        $placeholders = implode(',', array_fill(0, count($value), '?'));
                        $sqlParts[] = "`{$field}` NOT IN ({$placeholders})";
                        $bindings = array_merge($bindings, $value);
                    }
                    break;
                case '$': // LIKE (包含)
                    $sqlParts[] = "`{$field}` LIKE ?";
                    $bindings[] = "%{$value}%";
                    break;
                case '^': // LIKE (开头)
                    $sqlParts[] = "`{$field}` LIKE ?";
                    $bindings[] = "{$value}%";
                    break;
                case 'BETWEEN': // BETWEEN
                    $itemArr = explode(',', $value);
                    $sqlParts[] = "`{$field}` BETWEEN ? AND ?";
                    $bindings[] = trim($itemArr[0]);
                    $bindings[] = trim($itemArr[1]);
                    break;
                case '%': // REGEXP
                    $sqlParts[] = "`{$field}` REGEXP ?";
                    $bindings[] = $value;
                    break;
                case '>':
                case '<':
                case '>=':
                case '<=':
                case '!=':
                    $sqlParts[] = "`{$field}` {$actualOp} ?";
                    $bindings[] = $value;
                    break;
                default: // 等于
                    $sqlParts[] = "`{$field}` = ?";
                    $bindings[] = $value;
                    break;
            }
        }

        return [
            'sql' => implode(" {$operator} ", $sqlParts),
            'bindings' => $bindings
        ];
    }

    protected function buildModel()
    {
        // 无需构建模型
    }
} 