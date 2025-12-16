<?php

namespace Imee\Controller\Validation\Operate\Pretty\Commodity;

use Imee\Comp\Common\Validation\Validator;

class ListValidation extends ExportValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['page'] = 'required|integer';
        $rules['limit'] = 'required|integer|between:1,1000';
        return $rules;
    }
}
