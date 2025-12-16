<?php

namespace Imee\Controller\Validation\Luckywheel;

use Imee\Comp\Common\Validation\Validator;

class TicketsPriceValidation extends Validator
{
    protected function rules()
    {
        return [
            'config_1' => 'required|integer|min:0|max:100000',
            'config_2' => 'required|integer|min:0|max:100000',
            'config_3' => 'integer|min:0|max:100000',
            'config_4' => 'integer|min:0|max:100000',
            'config_5' => 'integer|min:0|max:100000',
            'config_6' => 'integer|min:0|max:100000',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'config_1' => '入场费1',
            'config_2' => '入场费2',
            'config_3' => '入场费3',
            'config_4' => '入场费4',
            'config_5' => '入场费5',
            'config_6' => '入场费6',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        $msg = [];
        foreach ($this->attributes() as $k => $v) {
            $msg[$k . '.max'] = "$v 最小值0，最大值100000";
            $msg[$k . '.min'] = "$v 最小值0，最大值100000";
        }
        return $msg;
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