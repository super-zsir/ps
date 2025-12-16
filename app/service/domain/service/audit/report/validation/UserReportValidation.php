<?php

namespace Imee\Service\Domain\Service\Audit\Report\Validation;

use Imee\Comp\Common\Validation\Validator;

class UserReportValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'integer',
            'to' => 'integer',
            'state' => 'integer',
            'rid' => 'integer'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '举报人',
            'to' => '被举报人',
            'state' => '状态',
            'rid' => '房间id'
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