<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionOffsetHandle extends AbstractHandle
{
    protected $keyWord = '@offset';

    protected function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        if (!is_numeric($value) || $value < 0) {
            throw new ApiException(ApiException::MSG_ERROR, '@offset value must be integer that egt 0');
        }

        $this->condition->setOffset((int)$value);
        $this->unsetKey[] = $this->keyWord;
    }
}