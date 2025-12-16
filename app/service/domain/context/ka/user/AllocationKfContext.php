<?php

namespace Imee\Service\Domain\Context\Ka\User;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class AllocationKfContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var int 用户id
     */
    protected $uid;

    /**
     * @var int 客服id
     */
    protected $kfId;

    /**
     * @var string 来源
     */
    protected $source;
}