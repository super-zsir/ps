<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionAliasHandle extends AbstractHandle
{
    protected $keyWord = '@alias';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        
        if (!is_array($value)) {
            throw new ApiException(ApiException::MSG_ERROR, '@alias value must be array');
        }

        // 验证别名映射的安全性
        $safeAliases = [];
        foreach ($value as $field => $alias) {
            $field = trim($field);
            $alias = trim($alias);
            
            if (empty($field) || empty($alias)) continue;
            
            // 验证字段名和别名只允许字母、数字、下划线
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                throw new ApiException(ApiException::MSG_ERROR, "Invalid field name in @alias: {$field}");
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
                throw new ApiException(ApiException::MSG_ERROR, "Invalid alias name in @alias: {$alias}");
            }
            
            $safeAliases[$field] = $alias;
        }
        
        // 数组过滤 - 移除空值和无效映射
        $safeAliases = array_filter($safeAliases, function($alias, $field) {
            return !empty($field) && !empty($alias) && 
                   is_string($field) && is_string($alias) &&
                   preg_match('/^[a-zA-Z0-9_]+$/', $field) &&
                   preg_match('/^[a-zA-Z0-9_]+$/', $alias);
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($safeAliases)) {
            throw new ApiException(ApiException::MSG_ERROR, 'No valid aliases specified in @alias');
        }

        // 设置字段别名
        $this->condition->setFieldAliases($safeAliases);
        $this->unsetKey[] = $this->keyWord;
    }
} 