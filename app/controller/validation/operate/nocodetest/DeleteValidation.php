<?php

namespace Imee\Controller\Validation\Operate\Nocodetest;

use Imee\Comp\Common\Validation\Validator;

class DeleteValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'required|integer',
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }

}