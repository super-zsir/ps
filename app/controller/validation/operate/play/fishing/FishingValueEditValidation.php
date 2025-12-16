<?php

namespace Imee\Controller\Validation\Operate\Play\Fishing;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class FishingValueEditValidation extends FishingValueAddValidation
{
    protected function rules()
    {
        return parent::rules() + [
                'id' => 'required|integer|min:1'
            ];
    }
}