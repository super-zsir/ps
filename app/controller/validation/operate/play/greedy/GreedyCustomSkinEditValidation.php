<?php

namespace Imee\Controller\Validation\Operate\Play\Greedy;

class GreedyCustomSkinEditValidation extends GreedyCustomSkinAddValidation
{
    protected function rules()
    {
        return parent::rules() + [
                'id'      => 'required|integer',
                'skin_id' => 'required|string'
            ];
    }
}