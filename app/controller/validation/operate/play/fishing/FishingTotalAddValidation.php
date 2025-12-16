<?php

namespace Imee\Controller\Validation\Operate\Play\Fishing;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class FishingTotalAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'total_value' => 'required|integer|min:1',
            'percent'     => 'required|integer|min:1|max:100',
            'type'        => 'required|integer|in:1,-1,0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'          => 'id',
            'total_value' => 'Total',
            'percent'     => 'Percent',
            'type'        => 'Type',
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }
}