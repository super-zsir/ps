<?php

namespace Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck;

use Imee\Service\Domain\Context\PageContext;

/**
 * 封禁核查列表
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

    protected $status;

    protected $op;

    protected $source;

    protected $uid;

    /**
     * 封禁核查
     * @var boolean
     */
    protected $isCheckUserForbidden = false;
}
