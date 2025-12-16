<?php

namespace Imee\Controller\Validation\Operate\Play\Fishing;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class FishingTotalEditValidation extends FishingTotalAddValidation
{
    protected function rules()
    {
        return parent::rules() + [
                'id' => 'required|integer|min:1'
            ];
    }
}