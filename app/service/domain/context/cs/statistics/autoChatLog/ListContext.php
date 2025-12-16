<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\AutoChatLog;

use Imee\Service\Domain\Context\PageContext;

/**
 * 自动应答统计列表
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
     * 类型
     * @var int
     */
    protected $type;

    /**
     * 是否直接找人工
     * @var int
     */
    protected $isService;

    /**
     * 用户ID
     * @var int
     */
    protected $uid;

    /**
     * 标签
     * @var string
     */
    protected $tag;

    /**
     * 用户问题
     * @var string
     */
    protected $content;

    /**
     * 用户回复内容
     * @var string
     */
    protected $reply;

	/**
	 * 大区
	 * @var string
	 */
	protected $language;
}
