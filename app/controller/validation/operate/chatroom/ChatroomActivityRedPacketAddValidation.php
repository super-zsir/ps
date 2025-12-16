<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsChatroomBackground;
use Imee\Models\Xs\XsChatroomSetredpackage;

class ChatroomActivityRedPacketAddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'name'       => 'required|string',
            'icon'       => 'required|string',
            'deleted'    => 'required|integer|in:' . implode(',', array_keys(XsChatroomSetredpackage::$deletedMap)),
            'begin_time' => 'date',
            'end_time'   => 'date|after:begin_time',
            'ordering'   => 'integer|between:0,1000',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'         => 'ID',
            'name'       => '名称',
            'icon'       => '红包图片',
            'deleted'    => '禁用状态',
            'begin_time' => '开始时间',
            'end_time'   => '结束时间',
            'ordering'   => '排序值',
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