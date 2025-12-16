<?php

namespace Imee\Controller\Validation\Operate\Medal;

use Imee\Comp\Common\Validation\Validator;

class MedalIssuedValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'           => 'required|string',
            'medal'         => 'required|integer',
            'expire_time'   => 'required|integer',
            'reason'        => 'required|string'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '用户ID',
            'medal' => '勋章ID',
            'expire_time' => '有效期',
            'reason' => '下发理由',
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