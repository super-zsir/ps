<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionDistinctHandle extends AbstractHandle
{
    protected $keyWord = '@distinct';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        
        // 支持单字段或多字段去重
        if (is_string($value)) {
            $fields = explode(',', $value);
        } elseif (is_array($value)) {
            $fields = $value;
        } else {
            throw new ApiException(ApiException::MSG_ERROR, '@distinct value must be string or array');
        }

        // 验证字段名安全性
        $safeFields = [];
        foreach ($fields as $field) {
            $field = trim($field);
            if (empty($field)) continue;
            
            // 只允许字母、数字、下划线
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                throw new ApiException(ApiException::MSG_ERROR, "Invalid field name in @distinct: {$field}");
            }
            $safeFields[] = $field;
        }
        
        // 数组过滤 - 移除空值和无效字段
        $safeFields = array_filter($safeFields, function($field) {
            return !empty($field) && is_string($field) && preg_match('/^[a-zA-Z0-9_]+$/', $field);
        });

        if (empty($safeFields)) {
            throw new ApiException(ApiException::MSG_ERROR, 'No valid fields specified in @distinct');
        }

        // 设置去重模式
        $this->condition->setDistinctMode($safeFields);
        $this->unsetKey[] = $this->keyWord;
    }
} 