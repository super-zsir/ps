<?php

namespace Imee\Controller\Validation\Operate\Play\Horse;

use Imee\Comp\Common\Validation\Validator;

class ValueAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'contribute_value' => 'required',
            'percent'          => 'required|min:0|max:100',
            'cheat_percent'    => 'required|min:0|max:100',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'               => 'ID',
            'contribute_value' => 'Value',
            'percent'          => 'Max Value',
            'cheat_percent'    => 'Cheat2',
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