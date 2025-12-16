<?php

namespace Imee\Service\Domain\Service\Csms\Context\Databoard\Forbiddenrisk;

use Imee\Service\Domain\Context\PageContext;

class ListContext extends PageContext
{
    protected $beginTime;

    protected $endTime;

    protected $appId;

    protected $sort = 'id';

    protected $dir = 'desc';
}
