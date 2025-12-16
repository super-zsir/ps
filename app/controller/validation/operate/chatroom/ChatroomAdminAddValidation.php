<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsChatroom;

class ChatroomAdminAddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'uid'  => 'required|integer|min:1',
            'rid'  => 'required|integer|min:1',
            'role' => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'uid'  => '用户ID',
            'rid'  => '房间ID',
            'role' => '用户角色',
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