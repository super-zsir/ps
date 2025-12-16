<?php

namespace Imee\Controller\Validation\Operate\Giftwall;

class GiftWallConfigModifyValidation extends GiftWallConfigCreateValidation
{
    protected function rules(): array
    {
        $rules = parent::rules();
        $rules['config_id'] = 'required|integer';
        $rules['big_area'] = 'required|integer';
        return $rules;
    }
}
