<?php

namespace Imee\Controller\Validation\Audit\Sensitive;

use Imee\Comp\Common\Validation\Validator;

class AddValidation extends Validator
{
    protected function rules()
    {
        return [
            'type' => 'required|string',
            'sub_type' => 'required|string',
            'cond' => 'required|array',
            'cond.*' => 'required|string',
            'vague' => 'required|integer',

            'danger' => 'required|integer',
            'accurate' => 'required|integer',
            'language' => 'required|string',
            'text' => 'required|array',
            'reason' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'type' => '类型',
            'subType' => '二级分类',
            'cond' => '场景',
            'vague' => '是否拼音匹配',
            'accurate' => '是否精准匹配',
            'text' => '敏感词',
            'language' => '语言',
            'danger' => '危险等级',
            'reason' => '原因',
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}
