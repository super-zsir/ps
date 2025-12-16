<?php

namespace Imee\Controller\Validation\Operate\User;

use Imee\Comp\Common\Validation\Validator;

class OpenScreenCardSendValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'            => 'required|integer|min:1',
            'num'            => 'required|integer|min:1',
            'type'           => 'required|integer|in:1,2',
            'effective_hour' => 'required|integer|in:6,12,24,36',
            'expired_time'   => 'required',
            'can_send'       => 'required|integer|in:0,1',
            'reason'         => 'required|string',
        ];
    }

    protected function attributes()
    {
        return [
            'uid'            => '用户ID',
            'num'            => '发放数量',
            'type'           => '卡片类型',
            'effective_hour' => '卡片持续有效期',
            'expired_time'   => '过期时间',
            'can_send'       => '是否可赠送',
            'reason'         => '发放理由',
        ];
    }

    protected function messages()
    {
        return [];
    }

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