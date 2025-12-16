<?php

namespace Imee\Controller\Validation\Operate\Play\Room;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class RoomRegionSwitchValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'             => 'required|integer',
            'coin_switch'    => 'required|integer',
            'diamond_switch' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'             => '大区ID',
            'coin_switch'    => '金币场开关',
            'diamond_switch' => '钻石场开关',
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }
}