<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

use Imee\Comp\Common\Validation\Validator;

class ValueAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'value'     => 'required',
            'max_value' => 'required|min:0|max:100',
            'percent'   => 'required|min:0|max:100',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'        => 'ID',
            'value'     => 'Value',
            'max_value' => 'Max Value',
            'percent'   => 'Percent',
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