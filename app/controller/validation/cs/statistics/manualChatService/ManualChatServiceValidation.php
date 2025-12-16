<?php

namespace Imee\Controller\Validation\Cs\Statistics\ManualChatService;

use Imee\Comp\Common\Validation\Validator;

class ManualChatServiceValidation extends Validator
{
    protected function rules()
    {
        return [
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'service_uid' => 'integer',
            'service' => 'string',
            'language' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'service_uid' => '客服UID',
            'service' => '通道',
            'language' => '语言',
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
