<?php

namespace Imee\Controller\Validation\Operate\Banner;

class ModifyShopValidation extends CreateShopValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['id'] = 'required|integer|min:1';
        $rules['deleted'] = 'required|integer|min:0';
        return $rules;
    }
}
