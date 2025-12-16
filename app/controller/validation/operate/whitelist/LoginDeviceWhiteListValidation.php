<?php

namespace Imee\Controller\Validation\Operate\Whitelist;

use Imee\Comp\Common\Validation\Validator;

class LoginDeviceWhiteListValidation extends Validator
{
    protected function rules()
    {
        return [
            'object_id' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'object_id' => 'Mac',
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}