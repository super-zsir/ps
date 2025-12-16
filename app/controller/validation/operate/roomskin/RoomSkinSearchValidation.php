<?php

namespace Imee\Controller\Validation\Operate\Roomskin;

use Imee\Comp\Common\Validation\Validator;

class RoomSkinSearchValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'          => 'required|integer',
            'skin_id'      => 'required|string',
            'use_term_day' => 'required|integer|min:0'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid'          => 'UID',
            'skin_id'      => '房间皮肤ID',
            'use_term_day' => '回收天数',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'use_term_day.min' => '回收天数输入有误'
        ];
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