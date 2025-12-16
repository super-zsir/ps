<?php

namespace Imee\Service\Domain\Service\Csms\Context\Saas;

use Imee\Service\Domain\Context\BaseContext;

class AuditOperatorContext extends BaseContext
{
    public $id;

    /**
     * 审核项标识
     */
    public $choice;

    /**
     * 审核项名称
     */
    public $choiceName;

    /**
     * 类型enum
     */
    public $type;

    /**
     * 所属产品
     */
    public $product;

    public $admin;

    public $state;

    public $joinup;
}
