<?php

namespace Imee\Controller\Validation\Operate\Livevideo;

use Imee\Comp\Common\Validation\Validator;

class RoomBottomConfigValidation extends Validator
{

    protected function rules()
    {
        return [
            'uid'      => 'required|integer|min:1',
            'property' => 'required|integer|min:1',
            'op_type'  => 'required|integer|min:1',
            'minutes'  => 'required|integer|min:1',
            'reason'   => 'string',
            'rid'      => 'integer|min:1',
        ];


    }

    protected function attributes()
    {
        return [
            'uid'      => '房主uid',
            'rid'      => '房间rid',
            'property' => '房间属性',
            'op_type'  => '操作类型',
            'minutes'  => '分钟数',
            'reason'   => '原因',
        ];
    }

    protected function response()
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }

    protected function messages()
    {
        return [];
    }
}