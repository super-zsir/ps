<?php

namespace Imee\Controller\Validation\Message;

use Imee\Comp\Common\Validation\Validator;

class ChatMessageListValidation extends Validator
{
    protected function rules()
    {
        return [
            'sid' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [];
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
