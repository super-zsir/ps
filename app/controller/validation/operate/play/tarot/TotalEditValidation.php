<?php

namespace Imee\Controller\Validation\Operate\Play\Tarot;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class TotalEditValidation extends TotalAddValidation
{
    protected function rules()
    {
        return parent::rules() + [
            'id' => 'required|integer'
        ];
    }
}