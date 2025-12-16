<?php

namespace Imee\Controller\Validation\Operate\Certification;

use Imee\Comp\Common\Validation\Validator;

class CertificationSendValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'uid'               =>  'required|string',
            'cer_id'            =>  'required|integer',
            'content'           =>  'required|string|max:30',
            'valid_day'         =>  'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'uid'              =>  'UID',
            'cer_id'           =>  'Material ID',
            'content'          =>  'Content',
            'valid_day'        =>  'Valid Days',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'content.max' => '{attr} 最大长度为30'
        ];
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