<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class ServicerOperationValidation extends Validator
{
    protected function rules()
    {
        return [
            'mark' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'mark' => '服务商标记',
            'name' => '服务商名称',
            'type' => '类型',
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
