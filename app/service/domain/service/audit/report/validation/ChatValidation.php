<?php

namespace Imee\Service\Domain\Service\Audit\Report\Validation;

use Imee\Comp\Common\Validation\Validator;

class ChatValidation extends Validator
{
    protected function rules()
    {
        return [
            'from' => 'required|integer',
            'to' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'from' => '发送人',
            'to' => '接收人',
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