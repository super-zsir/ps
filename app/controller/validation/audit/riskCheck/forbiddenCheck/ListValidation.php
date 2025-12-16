<?php

namespace Imee\Controller\Validation\Audit\RiskCheck\ForbiddenCheck;

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
            'start' => 'required|date',
            'end' => 'required|date',
            'status' => 'integer',
            'op' => 'integer',
            'source' => 'string',
            'uid' => 'integer',
            
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'start' => '开始时间',
            'end' => '结束时间',
            'status' => '审核状态',
            'op' => '封禁人',
            'source' => '封禁来源',
            'uid' => '用户ID',
            
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
