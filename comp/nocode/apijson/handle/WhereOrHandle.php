<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

/**
 * 处理 OR 查询
 * "key1|key2": "value"
 */
class WhereOrHandle extends AbstractHandle
{
    protected function buildModel()
    {
        foreach ($this->condition->getCondition() as $key => $value) {
            if (strpos($key, '|') === false) {
                continue;
            }

            $fields = explode('|', $key);
            $sqlParts = [];
            $bindings = [];

            foreach ($fields as $field) {
                $field = $this->sanitizeKey($field);
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                    continue; // 忽略非法字段
                }
                $sqlParts[] = "`{$field}` = ?";
                $bindings[] = $value;
            }

            if (count($sqlParts) > 1) {
                $sql = '(' . implode(' OR ', $sqlParts) . ')';
                $this->condition->addQueryWhere($key, $sql, $bindings);
                $this->unsetKey[] = $key;
            }
        }
    }
} 