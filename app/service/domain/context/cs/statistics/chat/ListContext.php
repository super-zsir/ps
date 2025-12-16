<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\Chat;

use Imee\Service\Domain\Context\PageContext;

/**
 * 客服满意度统计列表
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';
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
	 * 大区
	 * @var string
	 */
	protected $bigArea;
}
