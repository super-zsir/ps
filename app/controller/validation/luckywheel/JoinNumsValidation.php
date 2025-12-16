<?php

namespace Imee\Controller\Validation\Luckywheel;

use Imee\Comp\Common\Validation\Validator;

class JoinNumsValidation extends Validator
{
    protected function rules()
    {
        return [
            'config_1' => 'required|integer|min:2|max:10',
            'config_2' => 'required|integer|min:2|max:10',
            'config_3' => 'integer|min:2|max:10',
            'config_4' => 'integer|min:2|max:10',
            'config_5' => 'integer|min:2|max:10',
            'config_6' => 'integer|min:2|max:10',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'config_1' => '参与人数上限1',
            'config_2' => '参与人数上限2',
            'config_3' => '参与人数上限3',
            'config_4' => '参与人数上限4',
            'config_5' => '参与人数上限5',
            'config_6' => '参与人数上限6',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        $msg = [];
        foreach ($this->attributes() as $k => $v) {
            $msg[$k . '.max'] = "$v 最小值2，最大值10";
            $msg[$k . '.min'] = "$v 最小值2，最大值10";
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