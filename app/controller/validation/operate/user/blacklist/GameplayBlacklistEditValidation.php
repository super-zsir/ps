<?php

namespace Imee\Controller\Validation\Operate\User\Blacklist;

use Imee\Comp\Common\Validation\Validator;

class GameplayBlacklistEditValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'         => 'required|integer',
            'time_type'  => 'required|integer',
            'start_time' => 'required_if:time_type,2|date',
            'end_time'   => 'required_if:time_type,2|date|after:start_time',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'         => 'ID',
            'time_type'  => '黑名单时效',
            'start_time' => '生效时间',
            'end_time'   => '结束时间',
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }
}