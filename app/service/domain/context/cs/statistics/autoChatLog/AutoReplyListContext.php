<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\AutoChatLog;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 自动回复结果统计
 */
class AutoReplyListContext extends BaseContext
{
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
	 * 语言
	 * @var string
	 */
	protected $language;
}
