<?php

namespace Imee\Controller\Validation\Operate\Pop;

class PopRecommendModifyValidation extends PopRecommendCreateValidation
{
    protected function rules()
    {
        return parent::rules() + [
            'id' => 'required|integer',
        ];
    }
}