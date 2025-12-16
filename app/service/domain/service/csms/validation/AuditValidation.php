<?php


namespace Imee\Service\Domain\Service\Csms\Validation;

use Imee\Comp\Common\Validation\Validator;

class AuditValidation extends Validator
{
    public function rules()
    {
        return [];
    }

    public function attributes()
    {
        return [];
    }

    /**
     * 提示信息
     */
    public function messages()
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    public function response()
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
