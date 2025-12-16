<?php

namespace Imee\Controller\Validation\Operate\Chatroom;


class ChatroomActivityRedPacketEditValidation extends ChatroomActivityRedPacketAddValidation
{
    protected function rules(): array
    {
        return parent::rules() + [
                'id' => 'required|integer|min:1'
            ];
    }
}