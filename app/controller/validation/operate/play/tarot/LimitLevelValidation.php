<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class LimitLevelValidation extends Validator
{
    protected function rules()
    {
        return [
            'big_area_id' => 'required|integer',
            'limit_level' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'big_area_id' => '大区',
            'limit_level' => '等级配置',
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