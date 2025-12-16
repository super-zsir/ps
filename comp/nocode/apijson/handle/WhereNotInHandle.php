<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class WhereNotInHandle extends AbstractHandle
{
    protected function buildModel()
    {
        foreach (array_filter($this->condition->getCondition(), function($key){
            return str_ends_with($key, '!{}');
        }, ARRAY_FILTER_USE_KEY) as $key => $value)
        {
            if (!is_array($value) || empty($value)) {
                throw new ApiException(ApiException::MSG_ERROR, $key. ' value must be array');
            }

            $filteredValues = array_filter($value, function($item) {
                return $item !== null && $item !== '';
            });

            if (empty($filteredValues)) {
                $this->condition->addQueryWhere($key, '1 = 0');
                $this->unsetKey[] = $key;
                continue;
            }

            // 创建占位符
            $placeholders = implode(',', array_fill(0, count($filteredValues), '?'));
            $sql = sprintf('`%s` NOT IN (%s)', $this->sanitizeKey($key), $placeholders);

            $this->condition->addQueryWhere($key, $sql, array_values($filteredValues));
            $this->unsetKey[] = $key;
        }
    }
}