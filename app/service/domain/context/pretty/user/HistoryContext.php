<?php


namespace Imee\Service\Domain\Context\Pretty\User;

use Imee\Service\Domain\Context\PageContext;

/**
 * 靓号管理
 */
class HistoryContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    /**
     * @var int id
     */
    protected $id;
}
