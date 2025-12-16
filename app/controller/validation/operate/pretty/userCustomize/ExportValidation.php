<?php

namespace Imee\Controller\Validation\Operate\Pretty\UserCustomize;

use Imee\Comp\Common\Validation\Validator;

class ExportValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'integer',
            'uid' => 'integer',
            'status' => 'integer',
            'create_dateline_sdate' => 'date',
            'create_dateline_edate' => 'date',
            'customize_pretty_id' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => 'ID',
            'uid' => 'UID',
            'customize_pretty_id' => '自选靓号类型',
            'status' => '状态',
            'create_dateline_sdate' => '起始时间',
            'create_dateline_edate' => '结束时间',
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
