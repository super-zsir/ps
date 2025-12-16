<?php

namespace Imee\Service\Domain\Service\Audit\Report\Context;

use Imee\Service\Domain\Context\PageContext;

class ChatContext extends PageContext
{
    protected $from;
    protected $to;
    protected $admin;
    protected $fromUserId;
    protected $sid;
}