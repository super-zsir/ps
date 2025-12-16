<?php

namespace Imee\Controller\Validation\Operate\Pretty\Style;

use Imee\Comp\Common\Validation\Validator;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page'  => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'id'    => 'integer',
            'name'  => 'string',
            'disabled' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'   => '类型ID',
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
                'code'    => 0,
                'msg'     => '',
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}
