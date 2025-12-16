<?php

namespace Imee\Controller\Validation\Luckygift;

use Imee\Comp\Common\Validation\Validator;

class LimitConfigValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'integer',
            'bet_money' => 'required|integer',
            'amount' => 'required|integer',
            'rate' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => 'ID',
            'bet_money' => 'diamonds in a single round',//单轮下注金额
            'amount' => 'patterns in a single round',//下注项数量
            'rate' => 'probability',//触发预期
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response()
    {
        return [
            'result' => [
                'success' => true,
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}