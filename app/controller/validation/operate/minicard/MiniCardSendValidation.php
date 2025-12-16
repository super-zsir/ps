<?php

namespace Imee\Controller\Validation\Operate\Minicard;

use Imee\Comp\Common\Validation\Validator;

class MiniCardSendValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'         => 'required|string',
            'resource_id' => 'required|integer',
            'days'        => 'required|integer|min:1',
            'period_days' => 'required|integer|min:1',
            'num'         => 'required|integer|min:1|max:1000',
            'can_give'    => 'required|integer|in:0,1',
            'remark'      => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid'         => 'UID',
            'resource_id' => '资源ID',
            'days'        => '装扮天数',
            'period_days' => '资格使用有效天数',
            'num'         => '发放数量',
            'can_give'    => '是否可转赠',
            'remark'      => '备注',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            '*.min' => ':attribute 数值范围或长度最小 :min',
            '*.max' => ':attribute 数值范围或长度最大 :max',
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
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}