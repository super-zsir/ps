<?php

namespace Imee\Controller\Validation\Operate\Pretty\Style;

use Imee\Comp\Common\Validation\Validator;

class ModifyValidation extends CreateValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['id'] = 'required|integer';
        return $rules;
    }
}
