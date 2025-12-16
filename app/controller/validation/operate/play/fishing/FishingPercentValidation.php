<?php

namespace Imee\Controller\Validation\Operate\Play\Fishing;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class FishingPercentValidation extends Validator
{
    protected function rules()
    {
        return [
            'fish_percent_list'           => 'required|array',
            'fish_percent_list.*.fishid'  => 'required|integer|min:1',
            'fish_percent_list.*.odds'    => 'required|integer|min:1',
            'fish_percent_list.*.speed'   => 'required|integer|min:1',
            'fish_percent_list.*.quality' => 'required|integer|min:1',
            'fish_percent_list.*.k0'      => 'required|numeric|min:0',
            'fish_percent_list.*.kz'      => 'required|numeric|min:0',
            'fish_percent_list.*.k1'      => 'required|numeric|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'fish_percent_list'           => '配置',
            'fish_percent_list.*.fishid'  => 'fishid',
            'fish_percent_list.*.odds'    => 'odds',
            'fish_percent_list.*.speed'   => 'speed',
            'fish_percent_list.*.quality' => 'quality',
            'fish_percent_list.*.k0'      => 'type: 0',
            'fish_percent_list.*.kz'      => 'type: -1',
            'fish_percent_list.*.k1'      => 'type: 1',
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