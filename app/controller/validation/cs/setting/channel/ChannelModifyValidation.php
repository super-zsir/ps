<?php

namespace Imee\Controller\Validation\Cs\Setting\Channel;

use Imee\Comp\Common\Validation\Validator;

class ChannelModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'       => 'required|integer',
            'language' => 'array',
        ];
    }

    protected function attributes()
    {
        return [
            'language' => '语言',
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