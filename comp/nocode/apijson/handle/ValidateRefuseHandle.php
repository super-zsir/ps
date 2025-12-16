<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class ValidateRefuseHandle extends AbstractHandle
{
    const REFUSE_KEY = '@refuse';

    public function handle()
    {
        $condition = $this->condition->getCondition();
        if (!isset($condition[self::REFUSE_KEY])) {
            return;
        }

        $refuseKeys = $condition[self::REFUSE_KEY];
        if (!is_array($refuseKeys)) {
            $this->unsetKey[] = self::REFUSE_KEY;
            return; // @refuse 的值必须是数组
        }

        $requestKeys = array_keys($condition);

        foreach ($refuseKeys as $key) {
            if (in_array($key, $requestKeys)) {
                throw new ApiException(ApiException::MSG_ERROR, "请求中包含禁止的字段: {$key}");
            }
        }

        // 校验完成后，从条件中移除 @refuse
        $this->unsetKey[] = self::REFUSE_KEY;
    }

    protected function buildModel()
    {
        // 该 Handle 无需构建模型
    }
} 