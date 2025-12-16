<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

class ChatroomBackgroundEditValidation extends ChatroomBackgroundAddValidation
{
    protected function rules(): array
    {
        return parent::rules() + [
                'id' => 'required|integer|min:1'
            ];
    }
}