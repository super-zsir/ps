<?php

namespace Imee\Service\Domain\Context\Cs\Session\Chat;

use Imee\Service\Domain\Context\PageContext;

/**
 * 自动应答统计列表
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    /**
     * 通道
     * @var int
     */
    protected $service;

    /**
     * 客服ID
     * @var int
     */
    protected $serviceUid;

    /**
     * 用户ID
     * @var int
     */
    protected $uid;

    /**
     * 结束原因
     * @var string
     */
    protected $reason;

    /**
     * 满意评价
     * @var string
     */
    protected $vote;

	/**
	 * 语言
	 * @var string
	 */
	protected $language;

    /**
     * 会话标签
     * @var int
     */
    protected $chatType;

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
}
