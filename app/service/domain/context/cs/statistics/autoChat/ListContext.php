<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\AutoChat;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 自动回复数据统计
 */
class ListContext extends BaseContext
{
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
     * 统计类型
     * @var int
     */
    protected $statisticalType;

    /**
     * 标签
     * @var string
     */
    protected $tag;

	/**
	 * 大区
	 * @var string
	 */
	protected $language;
}
