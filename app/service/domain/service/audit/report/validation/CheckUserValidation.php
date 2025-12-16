<?php

namespace Imee\Service\Domain\Service\Audit\Report\Validation;

use Imee\Comp\Common\Validation\Validator;

class CheckUserValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'required|integer',
            'state' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => '举报id',
            'state' => '状态',
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