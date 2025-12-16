<?php

namespace Imee\Controller\Validation\Operate\Play\Crash;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class OddsValidation extends Validator
{
    protected function rules()
    {
        return [
            'tid'             => 'required|integer',
            'data'            => 'required|array',
            'data.*.timeline' => 'required|numeric',
            'data.*.odds'     => 'required|numeric',
            'data.*.rate'     => 'required|numeric',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'tid'             => '类型ID',
            'data'            => '预期表',
            'data.*.timeline' => 'time',
            'data.*.odds'     => 'odds',
            'data.*.rate'     => 'percent',
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