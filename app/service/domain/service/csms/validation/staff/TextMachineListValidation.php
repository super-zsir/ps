<?php


namespace Imee\Service\Domain\Service\Csms\Validation\Staff;

use Imee\Service\Domain\Service\Csms\Validation\AuditValidation;

/**
 * 文本 机身列表验证
 * Class TextMachineListValidation
 * @package Imee\Controller\Validation\Audit\Staff
 */
class TextMachineListValidation extends AuditValidation
{
    public function rules()
    {
        return [
            'begin_time' => 'date',
            'end_time' => 'date',
            'table' => 'string',
            'pk_value' => 'integer',
            'reason' => 'string',
            'sex' => 'integer',
            'app_ids' => 'array',
            'page' => 'integer',
            'limit' => 'integer',
            'dir' => 'string',
            'sort' => 'string',
            'ids' => 'array',
            'deleted' => 'integer',
            'deleted2' => 'integer',
            'content' => 'string',
			'is_second' => 'boolean',
        ];
    }

    public function attributes()
    {
        return [

        ];
    }
}
