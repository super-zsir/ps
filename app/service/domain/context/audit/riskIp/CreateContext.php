<?php

namespace Imee\Service\Domain\Context\Audit\RiskIp;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 创建
 */
class CreateContext extends BaseContext
{
    /**
     * ip1
     * @var string
     */
    protected $ip1;

    /**
     * ip2
     * @var string
     */
    protected $ip2;

    /**
     * ip3
     * @var string
     */
    protected $ip3;

    /**
     * ip4
     * @var string
     */
    protected $ip4;

    /**
     * 备注
     * @var string
     */
    protected $mark;
}
