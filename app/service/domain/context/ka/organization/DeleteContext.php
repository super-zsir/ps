<?php

namespace Imee\Service\Domain\Context\Ka\Organization;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class DeleteContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var array id
     */
    protected $id;
}