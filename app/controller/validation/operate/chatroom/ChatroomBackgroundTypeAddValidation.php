<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

use Imee\Comp\Common\Validation\Validator;

class ChatroomBackgroundTypeAddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'type'  => 'required|string|max:10',
            'icon'  => 'required|string',
            'icon2' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'    => 'ID',
            'type'  => '背景类型',
            'icon'  => '背景图片',
            'icon2' => '缩略图片',
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