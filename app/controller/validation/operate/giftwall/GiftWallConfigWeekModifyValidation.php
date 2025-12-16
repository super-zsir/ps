<?php

namespace Imee\Controller\Validation\Operate\Giftwall;

use Imee\Comp\Common\Validation\Validator;

class GiftWallConfigWeekModifyValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'pool_num'    => 'required|integer',
            'price_start' => 'required|integer',
            'price_end'   => 'required|integer',
            'target_num' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'pool_num'    => 'id',
            'price_start' => '最小价格',
            'price_end'   => '最大价格',
            'target_num' => '数量',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}
