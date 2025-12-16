<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class TotalAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'value'         => 'required|min:0',
            'cheat_percent' => 'required|min:0|max:100',
//            'jp_percent'    => 'required|min:0|max:100',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'            => 'ID',
            'value'         => 'total',
            'cheat_percent' => 'cheat_percent',
//            'jp_percent'    => 'jp_percent',
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