<?php

namespace Imee\Service\Domain\Context\Ka\Organization;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class CreateGroupContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var string 部门名称
     */
    protected $orgName;

    /**
     * @var int 父级id
     */
    protected $pid;

    /**
     * @var int 权重
     */
    protected $weight;
}