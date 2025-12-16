<?php

namespace Imee\Controller\Validation\Operate\Play\Crash;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class ParamsValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'     => 'required|integer',
            'name'   => 'required|string',
            'weight' => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'     => 'ID',
            'name'   => 'name',
            'weight' => '数值',
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