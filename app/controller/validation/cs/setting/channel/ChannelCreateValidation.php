<?php

namespace Imee\Controller\Validation\Cs\Setting\Channel;

use Imee\Comp\Common\Validation\Validator;

class ChannelCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'       => 'required|integer',
            'service'   => 'array',
            'service.*' => 'integer',
        ];
    }

    protected function attributes()
    {
        return [
            'uid'     => '后台客服ID',
            'service' => '客服通道',
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