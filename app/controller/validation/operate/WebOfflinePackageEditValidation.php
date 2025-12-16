<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsWebPageResource;

class WebOfflinePackageEditValidation extends WebOfflinePackageAddValidation
{
    protected function rules()
    {
        return parent::rules() + [
                'id' => 'required|integer'
            ];
    }
}