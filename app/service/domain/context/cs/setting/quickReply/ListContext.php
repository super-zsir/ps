<?php

namespace Imee\Service\Domain\Context\Cs\Setting\QuickReply;

use Imee\Service\Domain\Context\PageContext;

/**
 * 快捷回复列表上下文
 */
class ListContext extends PageContext
{
	/**
	 * @var 分组ID
	 */
	protected $groupId;
	/**
	 * @var 快捷回复
	 */
	protected $content;
}
