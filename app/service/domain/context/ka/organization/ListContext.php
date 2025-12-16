<?php

namespace Imee\Service\Domain\Context\Ka\Organization;

use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class ListContext extends PageContext
{
    use AdminUidContextTrait;

    /**
     * @var int 分组id
     */
    protected $groupId;

    /**
     * @var int|array 客服id
     */
    protected $kfId;
}