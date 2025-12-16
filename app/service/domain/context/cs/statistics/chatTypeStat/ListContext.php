<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\ChatTypeStat;

use Imee\Service\Domain\Context\PageContext;

/**
 * 会话分类统计
 */
class ListContext extends PageContext
{
    protected $sort = 'date_time';

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

    /**
     * 客服通道
     * @var int
     */
    protected $service;

    /**
     * 主动会话方
     * @var int
     */
    protected $activeType;

	/**
	 * 大区
	 * @var string
	 */
    protected $bigArea;
}
