<?php

namespace Imee\Controller\Validation\Audit\RiskUser;

use Imee\Comp\Common\Validation\Validator;

class ModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'     => 'integer',
            'status' => 'integer',
        ];
    }

    protected function attributes()
    {
        return [
            'status' => '状态',
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
