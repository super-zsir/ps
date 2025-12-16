<?php

namespace Imee\Controller\Validation\Operate\Pretty\User;

use Imee\Comp\Common\Validation\Validator;

class ExportValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid' => 'integer',
            'pretty_uid' => 'string',
            'pretty_source' => 'integer',
            'status' => 'integer',
            'dateline_sdate' => 'date',
            'dateline_edate' => 'date',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '用户ID',
            'pretty_uid' => '靓号',
            'pretty_source' => '来源',
            'status' => '状态',
            'dateline_sdate' => '起始时间',
            'dateline_edate' => '结束时间',
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
