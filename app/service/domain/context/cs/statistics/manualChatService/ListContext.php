<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\ManualChatService;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 客服统计（人工客服数据）列表
 */
class ListContext extends BaseContext
{
    /**
     * 语言
     * @var string
     */
    protected $language;

    /**
     * 起始时间
     * @var string
     */
    protected $startTime;

    /**
     * 结束时间
     * @var string
     */
    protected $endTime;

    /**
     * 客服UID
     * @var int
     */
    protected $serviceUid;

    /**
     * 客服通道
     * @var int
     */
    protected $service;
}
