<?php

namespace Imee\Controller\Validation\Operate\Lighting;

use Imee\Comp\Common\Validation\Validator;

class NameIdLightingLogValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'group_id'    => 'required|integer',
            'uid'         => 'required',
            'days'        => 'required|integer',
            'period_days' => 'required|integer',
            'num'         => 'required|integer',
            'can_give'    => 'required|integer',
            'remark'      => 'string|max:255',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'group_id'    => '分组ID',
            'uid'         => '用户UID',
            'days'        => '装扮有效天数',
            'period_days' => '资格使用有效天数',
            'num'         => '发放数量',
            'can_give'    => '是否可转赠',
            'remark'      => '备注',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}