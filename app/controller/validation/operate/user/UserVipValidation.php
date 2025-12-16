<?php

namespace Imee\Controller\Validation\Operate\User;

use Imee\Comp\Common\Validation\Validator;

class UserVipValidation extends Validator
{
    public function translates(): array
    {
        return [
            'uid'    => '用户ID',
            'level'  => 'VIP等级',
            'day'    => 'VIP天数',
            'reason' => '备注',
            'type'   => '操作类型',
        ];
    }

    protected function rules()
    {
        return [
            'uid'    => 'required|integer|min:1',
            'level'  => 'required|integer|min:0',
            'day'    => 'required|integer|min:0',
            'type'   => 'required|integer|min:0',
            'reason' => 'required',
        ];
    }


    protected function attributes()
    {
        return [
            'uid'    => '用户ID',
            'level'  => 'VIP等级',
            'day'    => 'VIP天数',
            'reason' => '备注',
            'type'   => '操作类型',
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