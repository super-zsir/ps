<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class AuditOperateValidation extends Validator
{
    protected function rules()
    {
        return [
            'choice' => 'required|string',
            'choice_name' => 'required|string',
            'type' => 'required|in:text,image,audio,video,mixture',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'choice' => '审核项标识',
            'choice_name' => '审核项名称',
            'type' => '审核内容类型',
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
