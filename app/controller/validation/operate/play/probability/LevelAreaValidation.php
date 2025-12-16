<?php

namespace Imee\Controller\Validation\Operate\Play\Probability;

use Imee\Comp\Common\Validation\Validator;

class LevelAreaValidation extends Validator
{
    protected function rules()
    {
        return [
            'bigarea_id'    => 'required|integer',
            'level'         => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'bigarea_id' => '运营大区',
            'level'=>'等级配置',
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