<?php

namespace Imee\Controller\Validation\Cs\Statistics\AutoChat;

use Imee\Comp\Common\Validation\Validator;

class DetailValidation extends Validator
{
    protected function rules()
    {
        return [
            'start_ts' => 'integer|required',
            'end_ts' => 'integer|required',
            'type' => 'integer',
            'qid' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'start_ts' => '开始时间',
            'end_ts' => '结束时间',
            'type' => '问题类型',
            'qid' => '问题ID',
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
