<?php

namespace Imee\Controller\Validation\Audit\Sensitive;

use Imee\Comp\Common\Validation\Validator;

class RemoveValidation extends Validator
{
    protected function rules()
    {
        return [
            'text' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'text' => '敏感词',
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
