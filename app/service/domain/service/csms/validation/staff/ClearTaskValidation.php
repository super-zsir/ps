<?php


namespace Imee\Service\Domain\Service\Csms\Validation\Staff;

use Imee\Service\Domain\Service\Csms\Validation\AuditValidation;

class ClearTaskValidation extends AuditValidation
{
    public function rules()
    {
        return [
            'module' => 'required',
            'choice' => 'required',
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
