<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class ValidateMustHandle extends AbstractHandle
{
    const MUST_KEY = '@must';

    public function handle()
    {
        $condition = $this->condition->getCondition();
        if (!isset($condition[self::MUST_KEY])) {
            return;
        }

        $mustKeys = $condition[self::MUST_KEY];
        if (!is_array($mustKeys)) {
            $this->unsetKey[] = self::MUST_KEY;
            return; // @must 的值必须是数组
        }

        $requestKeys = array_keys($condition);

        foreach ($mustKeys as $key) {
            if (!in_array($key, $requestKeys)) {
                throw new ApiException(ApiException::MSG_ERROR, "缺少必须的字段: {$key}");
            }
        }

        // 校验完成后，从条件中移除 @must，避免干扰后续操作
        $this->unsetKey[] = self::MUST_KEY;
    }

    protected function buildModel()
    {
        // 该 Handle 无需构建模型
    }
} 