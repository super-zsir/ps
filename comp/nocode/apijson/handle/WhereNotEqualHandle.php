<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

/**
 * 处理 Not Equal 查询
 * "key!": "value"
 */
class WhereNotEqualHandle extends AbstractHandle
{
    protected function buildModel()
    {
        foreach (array_filter($this->condition->getCondition(), function ($key) {
            return str_ends_with($key, '!');
        }, ARRAY_FILTER_USE_KEY) as $key => $value) {

            $field = $this->sanitizeKey($key);
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                continue; // 忽略非法字段
            }

            $sql = "`{$field}` != ?";
            $this->condition->addQueryWhere($key, $sql, [$value]);
            $this->unsetKey[] = $key;
        }
    }
} 