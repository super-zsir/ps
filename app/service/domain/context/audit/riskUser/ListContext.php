<?php

namespace Imee\Service\Domain\Context\Audit\RiskUser;

use Imee\Service\Domain\Context\PageContext;

/**
 * 风险用户审核列表
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    /**
     * 起始时间
     * @var string
     */
    protected $start;

    /**
     * 结束时间
     * @var string
     */
    protected $end;

    protected $uid;

    protected $status;

    protected $language;

    protected $reason;

	protected $type;
}
