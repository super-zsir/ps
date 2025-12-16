<?php

namespace Imee\Controller\Validation\Operate\Pretty\Commodity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsCommodityPrettyInfo;

class ModifyValidation extends CreateValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        $rules['id'] = 'required|integer';
        $rules['on_sale_status'] = 'required|integer|in:' .
            implode(',', array_keys(XsCommodityPrettyInfo::$displayOnSaleStatus));

        return $rules;
    }
}
