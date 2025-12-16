<?php

namespace Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 核查状态修改
 */
class ModifyContext extends BaseContext
{
    /**
     * id
     * @var integer
     */
    protected $id;
    /**
     * uid
     * @var integer
     */
    protected $uid;
}
