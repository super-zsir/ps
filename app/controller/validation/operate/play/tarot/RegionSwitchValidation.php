<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class RegionSwitchValidation extends Validator
{
    protected function rules()
    {
        return [
            'big_area_id'        => 'required|integer',
            'switch'             => 'required|integer',
            'global_rank_switch' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'big_area_id'        => '大区',
            'switch'             => '是否开启',
            'global_rank_switch' => '全球榜单开关',
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