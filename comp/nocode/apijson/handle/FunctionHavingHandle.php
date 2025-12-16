<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionHavingHandle extends AbstractHandle
{
    protected $keyWord = '@having';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];

        // 安全修复：严格校验和清理having子句，防止SQL注入
        $havingConditions = explode(',', $value);
        $safeHavings = [];
        $bindings = [];

        foreach ($havingConditions as $condition) {
            $condition = trim($condition);
            
            // 匹配聚合函数和简单字段的 having 条件
            // 支持: COUNT(*) > 1, SUM(field) >= 100, AVG(field) != 0, field > 5 等
            if (preg_match('/^((?:[A-Z]+\([^)]*\))|(?:[a-zA-Z0-9_]+))\s*([<>=!]+)\s*(.+)$/i', $condition, $matches)) {
                $field = trim($matches[1]);
                $operator = trim($matches[2]);
                $bindValue = trim($matches[3]);

                // 校验聚合函数或字段名
                $isAggregateFunction = preg_match('/^(COUNT|SUM|AVG|MAX|MIN)\s*\([^)]*\)$/i', $field);
                $isSimpleField = preg_match('/^[a-zA-Z0-9_]+$/', $field);
                
                if (!$isAggregateFunction && !$isSimpleField) {
                    continue; // 忽略非法字段或函数
                }

                // 校验操作符
                $allowedOperators = ['>', '<', '=', '>=', '<=', '!=', '<>'];
                if (!in_array($operator, $allowedOperators)) {
                    continue; // 忽略非法操作符
                }

                // 校验绑定值（必须是数字）
                if (!is_numeric($bindValue)) {
                    continue; // 忽略非数字值
                }

                // 对于聚合函数直接使用，对于简单字段加反引号
                if ($isAggregateFunction) {
                    $safeHavings[] = "{$field} {$operator} ?";
                } else {
                    $safeHavings[] = "`{$field}` {$operator} ?";
                }
                
                $bindings[] = (float)$bindValue; // 确保是数字类型
            }
        }

        if (empty($safeHavings)) {
            throw new ApiException(ApiException::MSG_ERROR, 'No valid conditions specified in @having');
        }

        $this->condition->setHaving([
            'sql' => implode(' AND ', $safeHavings),
            'bind' => $bindings
        ]);
        $this->unsetKey[] = $this->keyWord;
    }
}