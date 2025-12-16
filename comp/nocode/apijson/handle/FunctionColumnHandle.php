<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionColumnHandle extends AbstractHandle
{
    protected $keyWord = '@column';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $column = $conditions[$this->keyWord];
        if (!is_string($column)) {
            throw new ApiException(ApiException::MSG_ERROR, '@column value must be string');
        }

        // 安全修复：智能分割字段，正确处理包含逗号的函数调用
        $columns = $this->smartSplitColumns($column);
        $safeColumns = [];
        foreach ($columns as $col) {
            $col = trim($col);
            // 支持 AS 别名，例如 "user_name:nickname" 或 "user_name as nickname"
            $parts = preg_split('/\s+(as|AS)\s+|\s*:\s*/', $col);
            $field = trim($parts[0]);

            // 校验字段名：支持简单字段、聚合函数和复杂表达式
            $isSimpleField = preg_match('/^[a-zA-Z0-9_]+$/', $field);
            $isStarField = $field === '*';
            
            // 改进的函数匹配：能正确处理包含引号的参数
            $isAggregateFunction = preg_match('/^(COUNT|SUM|AVG|MAX|MIN|CONCAT|UPPER|LOWER|SUBSTRING)\s*\(/i', $field) && 
                                   $this->isValidFunctionCall($field);
            
            if (!$isSimpleField && !$isAggregateFunction && !$isStarField) {
                continue; // 忽略非法字段
            }

            // 根据字段类型决定如何处理
            if ($isSimpleField) {
                $safeCol = '`' . $field . '`';
            } elseif ($isStarField) {
                $safeCol = '*';
            } else {
                // 聚合函数和复杂表达式直接使用（已通过正则验证）
                $safeCol = $field;
            }

            // 处理别名
            if (isset($parts[1])) {
                $alias = trim($parts[1]);
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
                    continue; // 忽略非法别名
                }
                $safeCol .= ' AS `' . $alias . '`';
            }

            $safeColumns[] = $safeCol;
        }

        if (empty($safeColumns)) {
            throw new ApiException(ApiException::MSG_ERROR, 'No valid columns specified in @column');
        }

        $this->condition->setColumn(implode(', ', $safeColumns));
        $this->unsetKey[] = $this->keyWord;
    }

    /**
     * 验证函数调用是否合法
     * 能正确处理包含引号、逗号的复杂函数参数
     * 
     * @param string $functionCall
     * @return bool
     */
    private function isValidFunctionCall(string $functionCall): bool
    {
        // 移除首尾空格
        $functionCall = trim($functionCall);
        
        // 检查基本格式：函数名(参数)
        if (!preg_match('/^[A-Z]+\s*\(/i', $functionCall)) {
            return false;
        }
        
        // 寻找函数名和开括号
        $pos = strpos($functionCall, '(');
        if ($pos === false) {
            return false;
        }
        
        $functionName = strtoupper(trim(substr($functionCall, 0, $pos)));
        $allowedFunctions = ['COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 'CONCAT', 'UPPER', 'LOWER', 'SUBSTRING'];
        
        if (!in_array($functionName, $allowedFunctions)) {
            return false;
        }
        
        // 验证括号是否匹配
        $openCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = $pos; $i < strlen($functionCall); $i++) {
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
                    // 找到匹配的结束括号，检查后面是否只有空格
                    $remaining = trim(substr($functionCall, $i + 1));
                    return empty($remaining); // 后面应该没有其他字符
                }
            }
        }
        
        // 如果到这里说明括号没有正确匹配
        return false;
    }

    /**
     * 智能分割字段列表，正确处理包含逗号的函数调用
     * 
     * @param string $column
     * @return array
     */
    private function smartSplitColumns(string $column): array
    {
        $columns = [];
        $current = '';
        $depth = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($column); $i++) {
            $char = $column[$i];
            
            // 处理引号
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                $inQuotes = false;
                $quoteChar = '';
                $current .= $char;
            } elseif ($inQuotes) {
                // 在引号内，直接添加字符
                $current .= $char;
            } elseif ($char === '(') {
                // 函数开始，增加深度
                $depth++;
                $current .= $char;
            } elseif ($char === ')') {
                // 函数结束，减少深度
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                // 只有在函数外部的逗号才作为分隔符
                if (!empty(trim($current))) {
                    $columns[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        // 添加最后一个字段
        if (!empty(trim($current))) {
            $columns[] = trim($current);
        }
        
        return $columns;
    }
}