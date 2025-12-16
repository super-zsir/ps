<?php
namespace Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck;

use Imee\Service\Domain\Context\PageContext;

/**
 * 历史记录
 */
class HistoryContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    protected $logId;
}
