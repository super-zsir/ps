<?php

namespace Imee\Service\Domain\Context\Ka\Organization;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class DeleteGroupContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var int id
     */
    protected $id;
}