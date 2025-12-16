<?php

namespace Imee\Controller\Validation\Cs\Statistics\Chat;

use Imee\Comp\Common\Validation\Validator;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'string',
            'dir' => 'string|in:asc,desc',
            'start_time' => 'date',
            'end_time' => 'date',
            'big_area' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'big_area' => '大区',
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
