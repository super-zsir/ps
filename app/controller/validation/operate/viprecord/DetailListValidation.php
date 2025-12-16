<?php

namespace Imee\Controller\Validation\Operate\Viprecord;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsVipRecord;

class DetailListValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'required|integer',
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
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
        ];
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