<?php

namespace Imee\Controller\Validation\Cs\Session\Chat;

use Imee\Comp\Common\Validation\Validator;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page'        => 'required|integer',
            'limit'       => 'required|integer|between:1,1000',
            'sort'        => 'string',
            'dir'         => 'string|in:asc,desc',
            'service'     => 'integer',
            'service_uid' => 'integer',
            'uid'         => 'integer',
            'reason'      => 'string',
            'vote'        => 'string',
            'language'    => 'string',
            'chat_type'   => 'integer',
            'start'       => 'date',
            'end'         => 'date',
        ];
    }

    protected function attributes()
    {
        return [
            'app'         => 'APP ID',
            'service'     => '通道',
            'service_uid' => '客服ID',
            'uid'         => '用户ID',
            'reason'      => '结束原因',
            'vote'        => '满意评价',
            'language'    => '语言',
            'chat_type'   => '会话标签',
            'start'       => '开始时间',
            'end'         => '结束时间',
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
                'total'   => 0,
                'msg'     => '',
                'data'    => [],
            ],
        ];
    }
}