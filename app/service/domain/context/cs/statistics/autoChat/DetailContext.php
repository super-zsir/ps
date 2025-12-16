<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\AutoChat;

use Imee\Service\Domain\Context\PageContext;

/**
 * 自动回复数据统计详情
 */
class DetailContext extends PageContext
{
    protected $sort = 'dateline';

    protected $dir = 'desc';

    /**
     * 起始时间
     * @var string
     */
    protected $startTs;

    /**
     * 结束时间
     * @var string
     */
    protected $endTs;

    /**
     * 问题类型
     * @var int
     */
    protected $type;

    /**
     * qid
     * @var int
     */
    protected $qid;
}
