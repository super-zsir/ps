<?php

namespace Imee\Controller\Validation\Operate\User\Blacklist;

use Imee\Comp\Common\Validation\Validator;

class GameplayBlacklistAddBatchValidation extends Validator
{
    protected function rules()
    {
        return [
            'data.*.uid'        => 'required|integer',
            'data.*.type'       => 'required|integer',
            'data.*.time_type'  => 'required|integer',
            'data.*.start_time' => 'required_if:time_type,2|date',
            'data.*.end_time'   => 'required_if:time_type,2|date|after:start_time',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'data.*.uid'        => '用户UID',
            'data.*.type'       => '黑名单类型',
            'data.*.time_type'  => '黑名单时效',
            'data.*.start_time' => '生效时间',
            'data.*.end_time'   => '结束时间',
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