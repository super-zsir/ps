<?php

namespace Imee\Controller\Validation\Operate\Roomskin;

use Imee\Comp\Common\Validation\Validator;

class RoomSkinConfigValidation extends Validator
{
    protected function rules()
    {
        return [
            'type'   => 'required|integer',
            'name'   => 'required|string',
            'cover'  => 'required|string',
            'status' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'type'   => '皮肤类型',
            'name'   => '皮肤名称',
            'cover'  => '皮肤封面',
            'status' => '是否支持下发',
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