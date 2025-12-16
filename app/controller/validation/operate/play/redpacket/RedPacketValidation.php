<?php

namespace Imee\Controller\Validation\Operate\Play\Redpacket;

use Imee\Comp\Common\Validation\Validator;

class RedPacketValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'            => 'required|integer',
            'switch'        => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'            => 'ID',
            'switch'        => '当前状态',
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
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}