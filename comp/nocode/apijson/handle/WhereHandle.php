<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

class WhereHandle extends AbstractHandle
{
    protected function buildModel()
    {
        foreach ($this->condition->getCondition() as $key => $value)
        {
            $parsedValue = $this->parseValue($value);
            $sql = sprintf("`%s` = %s", $this->sanitizeKey($key), $parsedValue['sql']);
            $this->condition->addQueryWhere($key, $sql, $parsedValue['bind']);
            $this->unsetKey[] = $key;
        }
    }
}