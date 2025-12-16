<?php

namespace Imee\Controller\Validation\Audit\SensitiveWords;

use Imee\Comp\Common\Validation\Validator;

class SensitiveWordsValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'integer',
            'start_time' => 'date',
            'end_time' => 'date'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => 'uid',
            'start_time' => '开始时间',
            'end_time' => '结束时间'
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
