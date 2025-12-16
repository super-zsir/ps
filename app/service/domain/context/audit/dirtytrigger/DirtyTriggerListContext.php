<?php

namespace Imee\Service\Domain\Context\Audit\Dirtytrigger;

use Imee\Service\Domain\Context\PageContext;

class DirtyTriggerListContext extends PageContext
{
    protected $state;

    protected $uid;

    protected $source;

    protected $beginTime;

    protected $endTime;

    protected $appId;

	protected $cond;
}
