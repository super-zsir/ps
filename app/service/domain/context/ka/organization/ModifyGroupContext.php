<?php

namespace Imee\Service\Domain\Context\Ka\Organization;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class ModifyGroupContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var int id
     */
    protected $id;

    /**
     * @var string 部门名称
     */
    protected $orgName;

    /**
     * @var int 权重
     */
    protected $weight;
}