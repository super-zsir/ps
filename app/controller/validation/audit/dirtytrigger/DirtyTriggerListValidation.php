<?php

namespace Imee\Controller\Validation\Audit\Dirtytrigger;

use Imee\Comp\Common\Validation\Validator;

class DirtyTriggerListValidation extends Validator
{
    protected function rules()
    {
        return [
            'source' => 'string',
            'app_id' => 'integer',
            'uid' => 'integer',
            'state' => 'integer',
            'begin_time' => 'date',
            'end_time' => 'date'
        ];
    }


    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'source' => '来源',
            'app_id' => 'APP类型',
            'uid' => 'uid',
            'state' => '审核状态',
            'begin_time' => '开始时间',
            'end_time' => '结束时间'
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
