<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionCountHandle extends AbstractHandle
{
    protected $keyWord = '@count';

    protected function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        if ($value !== '' && $value !== null) {
            throw new ApiException(ApiException::MSG_ERROR, '@count value must be empty string or null');
        }

        // 调试信息
        error_log("FunctionCountHandle - Setting count mode for table: " . $this->condition->getTableName());

        // 设置查询为 COUNT 模式
        $this->condition->setCountMode(true);
        $this->unsetKey[] = $this->keyWord;
    }
} 