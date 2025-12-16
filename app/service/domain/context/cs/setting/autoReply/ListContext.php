<?php
namespace Imee\Service\Domain\Context\Cs\Setting\AutoReply;

use Imee\Service\Domain\Context\PageContext;

class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    protected $tag;

    protected $subject;

    protected $answer;

    protected $type;
}
