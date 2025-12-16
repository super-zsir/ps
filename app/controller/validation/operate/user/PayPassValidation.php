<?php

namespace Imee\Controller\Validation\Operate\User;

use Imee\Comp\Common\Validation\Validator;

class PayPassValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'    => 'required',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => 'UID',
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