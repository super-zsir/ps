<?php

namespace Imee\Controller\Validation\Operate\Likematerial;

use Imee\Comp\Common\Validation\Validator;

class LiveVideoLikeMaterialEditValidation extends LiveVideoLikeMaterialAddValidation
{
    protected function rules(): array
    {
        $rule = parent::rules();
        $rule['id'] = 'required|integer';
        return $rule;
    }
}
