<?php

namespace Imee\Controller\Validation\Roombackground;

use Imee\Comp\Common\Validation\Validator;

class BackgroundSendValidation extends Validator
{
    protected function rules()
    {
        return [
            'bg_id'    => 'required|integer',
            'duration' => 'required|integer|min:1',
            'uid'      => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'bg_id'    => 'Background ID',
            'duration' => 'Duration',
            'uid'      => 'UID',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'integer' => 'invalid param',
            'string' => 'invalid param',
            'duration.min' => 'Duration 只能为正整数',
        ];
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
