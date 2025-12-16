<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

class WhereLikeHandle extends AbstractHandle
{
    protected function buildModel()
    {
        // 匹配 key$ (LIKE '%value') 和 key^ (LIKE 'value%')
        foreach (array_filter($this->condition->getCondition(), function ($key) {
            return substr($key, -1) === '$' || substr($key, -1) === '^';
        }, ARRAY_FILTER_USE_KEY) as $key => $value) {
            $realKey = rtrim($key, '^$');
            $op = substr($key, -1) === '$' ? 'LIKE' : 'LIKE'; // 都是LIKE
            $bindValue = substr($key, -1) === '$' ? '%' . $value : $value . '%';

            $field = $this->sanitizeKey($realKey);
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                continue; // 忽略非法字段
            }

            $sql = "`{$field}` {$op} ?";
            $this->condition->addQueryWhere($key, $sql, [$bindValue]);
            $this->unsetKey[] = $key;
        }
    }
}