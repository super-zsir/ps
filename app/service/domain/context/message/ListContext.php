<?php

namespace Imee\Service\Domain\Context\Message;

use Imee\Service\Domain\Context\PageContext;

/**
 * 私聊消息列表
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    protected $from;

    protected $to;
}
