<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Emoticons;

class EmoticonsEditValidation extends EmoticonsAddValidation
{
    protected function rules()
    {
        return [
            'id'                => 'required|integer',
            'group_id'          => 'required|integer',
            'bigarea'           => 'required|integer|min:1',
            'identity'          => 'required|integer',
            'emoticons_meta_id' => 'required|integer',
            'price'             => 'required_if:identity,5|integer|min:0',
            'durtion'           => 'required_if:identity,5|integer|min:0',
        ];
    }
}