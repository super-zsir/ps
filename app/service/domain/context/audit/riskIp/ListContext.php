<?php

namespace Imee\Service\Domain\Context\Audit\RiskIp;

use Imee\Service\Domain\Context\PageContext;

/**
 * 风险IP列表
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

	protected $ip;
}
