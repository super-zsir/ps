<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

class FunctionGroupHandle extends AbstractHandle
{
    protected $keyWord = '@group';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        $groupArr = explode(',', $value);

        $this->condition->setGroup($groupArr);
        $this->unsetKey[] = $this->keyWord;
    }
}