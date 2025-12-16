<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Tag;


class EmoticonsTagEditValidation extends EmoticonsTagAddValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['id'] = 'required|integer';
        return $rules;
    }
}