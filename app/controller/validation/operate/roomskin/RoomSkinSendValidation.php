<?php

namespace Imee\Controller\Validation\Operate\Roomskin;

use Imee\Comp\Common\Validation\Validator;

class RoomSkinSendValidation extends Validator
{
    protected function rules()
    {
        return [
            'commodity' => 'required|array',
            'uid'       => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'commodity' => '物品',
            'uid'       => 'UID',
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}