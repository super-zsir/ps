<?php

namespace Imee\Service\Domain\Service\Csms\Context\Saas;

use Imee\Service\Domain\Context\BaseContext;

class FieldContext extends BaseContext
{
    /**
     * @var int csms_choice_field.id
     */
    protected $fieldId;

    /**
     * @var string 字段名
     */
    protected $field;

    /**
     * @var string 风控类型
     */
    protected $type;

    /**
     * @var string 修改前
     */
    protected $before;

    /**
     * @var string 当前
     */
    protected $after;
}
