<?php

namespace Imee\Controller\Validation\Audit\RiskBlacklist;

use Imee\Comp\Common\Validation\Validator;

class AddValidation extends Validator
{
    protected function rules()
    {
        return [
            'type'          => 'required|string',
            'rule_content'  => 'required|string',
            'handle_method' => 'required|string',
            'status'        => 'required|integer|in:0,1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'type'          => '黑名单类型',
            'rule_content'  => '规则内容',
            'handle_method' => '处理方式',
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
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}
