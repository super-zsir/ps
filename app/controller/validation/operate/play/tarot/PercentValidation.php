<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class PercentValidation extends Validator
{
    protected function rules()
    {
        return [
            'number'           => 'required|integer',
            'odds'             => 'required|integer',
            'percent_0'        => 'required|min:0|max:100',
            'percent_1'        => 'required|min:0|max:100',
            'percent_-1'       => 'required|min:0|max:100',
            'percent_lucky'    => 'required|min:0|max:100',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'number'           => 'Number',
            'odds'             => 'Odds',
            'percent_0'        => '预期：0',
            'percent_-1'       => '预期：-1',
            'percent_1'        => '预期：1',
            'percent_lucky'    => '预期：lucky',
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