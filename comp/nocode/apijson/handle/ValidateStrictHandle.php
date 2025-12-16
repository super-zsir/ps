<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class ValidateStrictHandle extends AbstractHandle
{
    const STRICT_KEY = '@strict';
    const MUST_KEY = '@must';
    const REFUSE_KEY = '@refuse';

    public function handle()
    {
        $condition = $this->condition->getCondition();
        if (!isset($condition[self::STRICT_KEY]) || $condition[self::STRICT_KEY] !== true) {
            $this->unsetKey[] = self::STRICT_KEY;
            return;
        }

        $mustKeys = $condition[self::MUST_KEY] ?? [];
        $refuseKeys = $condition[self::REFUSE_KEY] ?? [];

        if (!is_array($mustKeys)) $mustKeys = [];
        if (!is_array($refuseKeys)) $refuseKeys = [];

        $declaredKeys = array_merge($mustKeys, $refuseKeys);
        $declaredKeys = array_unique($declaredKeys);

        $requestKeys = array_keys($condition);

        // 移除所有官方 @- 开头的指令键，它们不参与校验
        $actionKeys = array_filter($requestKeys, function ($key) {
            return strpos($key, '@') !== 0;
        });

        foreach ($actionKeys as $key) {
            if (!in_array($key, $declaredKeys)) {
                throw new ApiException(ApiException::MSG_ERROR, "严格模式：请求中包含未在 @must 或 @refuse 中声明的字段 '{$key}'");
            }
        }

        // 校验完成后，移除 @strict
        $this->unsetKey[] = self::STRICT_KEY;
    }

    protected function buildModel()
    {
        // 该 Handle 无需构建模型
    }
} 