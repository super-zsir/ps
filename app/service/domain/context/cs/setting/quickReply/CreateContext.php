<?php

namespace Imee\Service\Domain\Context\Cs\Setting\QuickReply;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 快捷回复创建上下文
 */
class CreateContext extends BaseContext
{
	/**
	 * 分组id
	 */
	protected $groupId;

    /**
     * 内容
     * @var string
     */
    protected $content;
}
