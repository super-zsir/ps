<?php

namespace Imee\Service\Domain\Service\Audit\Context\CircleReport;

use Imee\Service\Domain\Context\PageContext;

class TaskListContext extends PageContext
{
    protected $rpids;

    protected $status;

    protected $appIds;

    protected $roid;

    protected $uid;

    protected $ruid;

    protected $rotype;

    protected $sort = 'rpid';

    protected $dir = 'desc';

    protected $language;
}
