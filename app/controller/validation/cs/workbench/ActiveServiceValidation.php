<?php

namespace Imee\Controller\Validation\Cs\Workbench;

use Imee\Comp\Common\Validation\Validator;

class ActiveServiceValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'required|integer',
            'fromId' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '用户ID',
            'fromId' => '通道ID',
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
