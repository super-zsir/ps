<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

class WhereBetweenHandle extends AbstractHandle
{
    protected function buildModel()
    {
        foreach (array_filter($this->condition->getCondition(), function($key){
            return substr($key, -1) === '$';
        }, ARRAY_FILTER_USE_KEY) as $key => $value)
        {
            $value = !is_array($value) ? [$value] : $value;
            $sql = [];
            $bind = [];
            foreach ($value as $item) {
                $itemArr = explode(',', $item);
                $sql[] = sprintf("`%s` BETWEEN ? AND ?", $this->sanitizeKey($key));
                $bind = array_merge($bind, [trim($itemArr[0]), trim($itemArr[1])]);
            }
            $this->condition->addQueryWhere($key, join(' OR ', $sql), $bind);
            $this->unsetKey[] = $key;
        }
    }
}