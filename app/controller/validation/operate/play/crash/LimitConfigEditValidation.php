<?php

namespace Imee\Controller\Validation\Operate\Play\Crash;

class LimitConfigEditValidation extends LimitConfigAddValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['id'] = 'required|integer';
        return $rules;
    }
}