<?php

namespace Imee\Controller\Validation\Operate\Play\Crash;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class LimitConfigAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'config_type'  => 'required|integer',
            'value'        => 'required|integer',
            'percent'      => 'required|integer|min:0|max:100',
            'table_id'     => 'required_if:config_type,1,2|integer',
            'high_percent' => 'required_if:config_type,1,2|integer|min:0|max:100',
            'jp_percent'   => 'required_if:config_type,2|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'           => 'id',
            'config_type'  => 'config_type',
            'value'        => 'value',
            'percent'      => 'percent',
            'table_id'     => 'type',
            'high_percent' => 'high',
            'jp_percent'   => 'jp_down',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'percent.min' => 'percent不能小于0',
            'percent.max' => 'percent不能大于100',
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }
}