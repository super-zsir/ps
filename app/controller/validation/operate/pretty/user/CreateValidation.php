<?php

namespace Imee\Controller\Validation\Operate\Pretty\User;

use Imee\Comp\Common\Validation\Validator;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'required|integer',
            'pretty_uid' => 'required|string',
            'expire_time' => 'required|date',
            'mark' => '',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '用户ID',
            'pretty_uid' => '靓号',
            'expire_time' => '过期时间',
            'mark' => '备注'
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
