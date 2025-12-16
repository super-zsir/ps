<?php

namespace Imee\Controller\Validation\Cs\Workbench;

use Imee\Comp\Common\Validation\Validator;

class SessionListValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'integer',
            'uids' => 'string',
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
