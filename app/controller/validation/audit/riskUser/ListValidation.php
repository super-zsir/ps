<?php

namespace Imee\Controller\Validation\Audit\RiskUser;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsUserReaudit;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'string',
            'dir' => 'string|in:asc,desc',
            'start' => 'date',
            'end' => 'date',
            'uid' => 'integer',
            'status' => 'integer|in:'. implode(',', array_keys(XsUserReaudit::$status_arr)),
			'language' => 'string',
			'reason' => 'string',
            'type' => 'integer',
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
            'uid' => '用户ID',
            'status' => '状态',
            'language' => '语言',
            'reason' => '原因',
            'type' => '触发原因',
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
