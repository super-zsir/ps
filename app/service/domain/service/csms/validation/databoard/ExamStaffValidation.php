<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Databoard;

use Imee\Comp\Common\Validation\Validator;

class ExamStaffValidation extends Validator
{
    protected function rules()
    {
        return [
            'filter' => 'string',
            'start' => 'integer',
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'string',
            'dir' => 'string|in:asc,desc',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'module' => '模块',
            'choice' => '审核项',
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
