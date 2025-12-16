<?php


namespace Imee\Service\Domain\Service\Csms\Validation\Staff;

use Imee\Service\Domain\Service\Csms\Validation\AuditValidation;

class OldTaskInfoValidation extends AuditValidation
{
    public function rules()
    {
        return [
            'ids' => 'required|array'
        ];
    }

    public function attributes()
    {
        return [
            'ids' => '旧任务ID'
        ];
    }
}
