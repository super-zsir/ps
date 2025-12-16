<?php

namespace Imee\Service\Domain\Context\Audit\RiskUser;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 修改状态
 */
class ModifyContext extends BaseContext
{
    /**
     * id
     * @var integer
     */
    protected $id;

    /**
     * 状态
     * @var integer
     */
    protected $status;
}
