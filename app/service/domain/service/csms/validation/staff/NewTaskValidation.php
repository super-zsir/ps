<?php



namespace Imee\Service\Domain\Service\Csms\Validation\Staff;

use Imee\Service\Domain\Service\Csms\Validation\AuditValidation;

/**
 * 获取新任务验证
 * Class NewTaskValidation
 * @package Imee\Controller\Validation\Audit\Staff
 */
class NewTaskValidation extends AuditValidation
{
    public function rules()
    {
        return [
            'oldIds' => 'array',
            'power' => 'required|array',
            'num' => 'required|integer',
            'where' => 'array'
        ];
    }

    public function attributes()
    {
        return [
            
        ];
    }
}
