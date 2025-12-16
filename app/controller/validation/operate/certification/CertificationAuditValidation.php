<?php

namespace Imee\Controller\Validation\Operate\Certification;

use Imee\Comp\Common\Validation\Validator;

class CertificationAuditValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'      =>  'required|string',
            'state'   =>  'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'     =>  'ID',
            'state'  =>  '审核状态',
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