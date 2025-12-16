<?php

namespace Imee\Controller\Validation\Audit\Dirtytrigger;

use Imee\Comp\Common\Validation\Validator;

class DirtyValidValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'integer',
            'date' => 'date',
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'required|string',
            'dir' => 'required|string|in:asc,desc',
        ];
    }


    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '类型',
            'date' => '日期',
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
