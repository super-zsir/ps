<?php

namespace Imee\Controller\Validation\Operate\Push;

use Imee\Comp\Common\Validation\Validator;

class PushRuleValidation extends Validator
{
    protected function rules()
    {
        return [
            'name' => 'required|string',
            'member_data' => 'required|array',
            'is_exclusion' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name' => '规则名称',
            'member_data' => '添加计划',
            'is_exclusion' => '是否互斥',
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
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}