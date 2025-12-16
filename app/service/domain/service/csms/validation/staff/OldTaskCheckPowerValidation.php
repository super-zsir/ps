<?php


namespace Imee\Service\Domain\Service\Csms\Validation\Staff;

use Imee\Service\Domain\Service\Csms\Validation\AuditValidation;

class OldTaskCheckPowerValidation extends AuditValidation
{
    public function rules()
    {
        return [
            'power' => 'required|array',
            'info' => 'required|array',
        ];
    }

    public function attributes()
    {
        return [
            'power' => '审核权限',
            'info' => '审核任务'
        ];
    }
}
