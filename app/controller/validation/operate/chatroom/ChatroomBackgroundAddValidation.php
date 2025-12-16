<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsChatroomBackground;

class ChatroomBackgroundAddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'type'       => 'required|string|max:10',
            'rid'        => 'string',
            'deleted'    => 'required|integer|in:' . implode(',', array_keys(XsChatroomBackground::$deletedMap)),
            'begin_time' => 'date',
            'end_time'   => 'date|after:begin_time',
            'ordering'   => 'integer|between:0,1000',
            'language'   => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'         => 'ID',
            'type'       => '背景类型',
            'rid'        => '房间id',
            'deleted'    => '禁用状态',
            'begin_time' => '开始时间',
            'end_time'   => '结束时间',
            'ordering'   => '排序',
            'language'   => '语言',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}