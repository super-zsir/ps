<?php

namespace Imee\Controller\Validation\Roombackground;

use Imee\Comp\Common\Validation\Validator;

class BackgroundKnapsackEditValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'       => 'required|string',
            'bg_id'     => 'required|string',
            'where'     => 'required|integer',
            'duration'  => 'required|integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid'       => 'UID',
            'bg_id'     => 'Background ID',
            'where'     => 'Add/Deduction',
            'duration'  => 'Duration',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'integer'  => 'invalid param',
            'string'   => 'invalid param',
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
