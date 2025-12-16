<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class ProductOperateValidation extends Validator
{
    protected function rules()
    {
        return [
            'name' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name' => '产品名称',
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
