<?php

namespace Imee\Controller\Validation\Audit\RiskIp;

use Imee\Comp\Common\Validation\Validator;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'ip1'  => 'required|integer',
            'ip2'  => 'required|integer',
            'ip3'  => 'required|integer',
            'ip4'  => 'integer',
            'mark' => 'string',
        ];
    }

    protected function attributes()
    {
        return [
            'mark' => '备注',
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