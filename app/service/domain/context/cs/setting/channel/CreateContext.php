<?php

namespace Imee\Service\Domain\Context\Cs\Setting\Channel;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 角色创建
 */
class CreateContext extends BaseContext
{
    /**
     * 后台客服ID
     * @var integer
     */
    protected $uid;

    /**
     * 客服通道
     * @var array
     */
    protected $service;
}
