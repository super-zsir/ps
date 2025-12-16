<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Material;

class EmoticonsMaterialEditValidation extends EmoticonsMaterialAddValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['id'] = 'required|integer';
        return $rules;
    }
}