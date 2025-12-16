<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

class WhereOpHandle extends AbstractHandle
{
    protected function buildModel()
    {
        foreach ($this->condition->getCondition() as $key => $value)
        {
            if(str_ends_with($key, '>=')) {
                $op = '>=';
            } else if(str_ends_with($key, '<=')) {
                $op = '<=';
            } else if(str_ends_with($key, '>')) {
                $op = '>';
            } else if(str_ends_with($key, '<')) {
                $op = '<';
            }
            if (!isset($op)) continue;

            $parsedValue = $this->parseValue($value);
            $sql = sprintf("`%s` %s %s", $this->sanitizeKey($key), $op, $parsedValue['sql']);
            $this->condition->addQueryWhere($key, $sql, $parsedValue['bind']);
            $this->unsetKey[] = $key;
        }
    }
}