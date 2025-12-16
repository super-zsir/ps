<?php

namespace Imee\Controller\Validation\Operate\Play\Fishing;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class FishingFromValidation extends Validator
{
    protected function rules()
    {
        return [
            'data'             => 'required|array',
            'data.*.id'        => 'required|integer|min:0',
            'data.*.fishCount' => 'required|integer|min:0',
            'data.*.in1'       => 'required|integer|min:0',
            'data.*.out1'      => 'required|integer|min:0',
            'data.*.in2'       => 'required|integer|min:0',
            'data.*.out2'      => 'required|integer|min:0',
            'data.*.in3'       => 'required|integer|min:0',
            'data.*.out3'      => 'required|integer|min:0',
            'data.*.in4'       => 'required|integer|min:0',
            'data.*.out4'      => 'required|integer|min:0',
            'data.*.in5'       => 'required|integer|min:0',
            'data.*.out5'      => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [];
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