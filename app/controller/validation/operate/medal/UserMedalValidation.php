<?php

namespace Imee\Controller\Validation\Operate\Medal;

use Imee\Comp\Common\Validation\Validator;

class UserMedalValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'            => 'required|integer',
            'uid'           => 'required|string',
            'medal_id'      => 'required|integer',
            'expire_time'   => 'required|integer|min:0',
            'reason'        => 'string'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'  => 'ID',
            'uid' => '用户ID',
            'medal' => '勋章ID',
            'expire_time' => '扣除时间',
            'reason' => '备注',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'expire_time.min' => '失效时间为正整数'
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}