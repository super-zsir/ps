<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

class ChatroomAdminEditValidation extends ChatroomAdminAddValidation
{
    protected function rules(): array
    {
        return parent::rules() + [
                'id' => 'required|integer'
            ];
    }
}