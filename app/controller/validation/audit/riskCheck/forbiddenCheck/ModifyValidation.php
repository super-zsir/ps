<?php

namespace Imee\Controller\Validation\Audit\RiskCheck\ForbiddenCheck;

use Imee\Comp\Common\Validation\Validator;

class ModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'  => 'required|integer',
            'uid' => 'required|string',
        ];
    }

    protected function attributes()
    {
        return [
            'id'  => 'ID',
            'uid' => '用户ID',
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