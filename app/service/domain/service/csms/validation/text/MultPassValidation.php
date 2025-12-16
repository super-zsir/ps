<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Text;

use Imee\Service\Domain\Service\Csms\Validation\AuditValidation;

class MultPassValidation extends AuditValidation
{
    public function rules()
    {
        return [
            'module' => 'string',
            'choice' => 'string',
            'ids' => 'required',
            'deleted' => 'required'
        ];
    }

    public function attributes()
    {
        return [
            'module' => '模块',
            'choice' => '选项'
        ];
    }
}
