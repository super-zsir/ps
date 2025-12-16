<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionOrderHandle extends AbstractHandle
{
    protected $keyWord = '@order';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        $orderArr = explode(',', $value);
        $orderCondition = [];

        foreach ($orderArr as $order) {
            $order = trim($order);
            if (!$order) {
                // 忽略空的排序条件
                continue;
            }

            $field = str_replace(['-', '+'], '', $order);

            // 安全修复：严格校验排序字段名，只允许字母、数字和下划线
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                throw new ApiException(ApiException::MSG_ERROR, "Invalid character in order field: " . $field);
            }

            $direction = str_ends_with($order, '-') ? 'DESC' : 'ASC';
            $orderCondition[] = [$field, $direction];
        }

        if (empty($orderCondition)) {
            throw new ApiException(ApiException::MSG_ERROR, '@order value is incorrect or empty');
        }

        $this->condition->setOrder($orderCondition);
        $this->unsetKey[] = $this->keyWord;
    }
}