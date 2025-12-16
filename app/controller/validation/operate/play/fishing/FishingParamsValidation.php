<?php

namespace Imee\Controller\Validation\Operate\Play\Fishing;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class FishingParamsValidation extends Validator
{
    protected function rules()
    {
        return [
            'key'   => 'required|string',
            'value' => 'required|integer'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'key'   => 'Key',
            'value' => 'Value'
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