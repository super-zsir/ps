<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

class ChatroomBackgroundTypeEditValidation extends ChatroomBackgroundTypeAddValidation
{
    protected function rules(): array
    {
        return parent::rules() + [
                'id' => 'required|integer'
            ];
    }
}