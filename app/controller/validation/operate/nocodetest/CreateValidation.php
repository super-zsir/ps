<?php

namespace Imee\Controller\Validation\Operate\Nocodetest;

use Imee\Comp\Common\Validation\Validator;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'user_name'   => 'required|string',
            'user_email'  => 'string',
            'user_gender' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'user_name'   => '用户名',
            'user_email'  => '用户邮箱',
            'user_gender' => '用户性别',
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