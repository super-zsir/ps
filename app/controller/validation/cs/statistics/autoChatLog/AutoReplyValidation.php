<?php

namespace Imee\Controller\Validation\Cs\Statistics\AutoChatLog;

use Imee\Comp\Common\Validation\Validator;

class AutoReplyValidation extends Validator
{
    protected function rules()
    {
        return [
            'start_time' => 'date',
            'end_time' => 'date',
            'language' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            
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
