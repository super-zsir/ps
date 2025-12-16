<?php

namespace Imee\Controller\Validation\Cs\Statistics\FirstChatRecord;

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
            'start' => 'date',
            'end' => 'date',
            'from_big_area' => 'integer',
            'from_sex' => 'integer',
            'to_sex' => 'integer',
            'is_reply' => 'integer',
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
            'from_big_area' => '大区',
            'from_sex' => '发送者性别',
            'to_sex' => '接收者性别',
            'active_type' => '24h是否回复',
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
