<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

class ValueEditValidation extends ValueAddValidation
{
    protected function rules()
    {
        return parent::rules() + [
            'id' => 'required|integer'
        ];
    }
}