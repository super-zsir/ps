<?php

namespace Imee\Controller\Validation\Operate\Push;

use Imee\Comp\Common\Validation\Validator;

class PushCateAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'name' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name' => '类型名称',
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
                'data' => [],
            ],
        ];
    }
}