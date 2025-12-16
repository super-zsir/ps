<?php

namespace Imee\Controller\Validation\Operate\Activity\Honorwalltemplate;

class EditValidation extends AddValidation
{
    protected function rules(): array
    {
        return parent::rules() + [
                'id' => 'required|integer'
            ];
    }
}