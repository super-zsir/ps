<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Databoard;

use Imee\Comp\Common\Validation\Validator;

class TimeBoardValidation extends Validator
{
    protected function rules()
    {
        return [
            'date' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'date' => '日期',
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
