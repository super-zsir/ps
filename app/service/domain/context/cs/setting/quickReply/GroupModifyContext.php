<?php

namespace Imee\Service\Domain\Context\Cs\Setting\QuickReply;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 修改上下文
 */
class GroupModifyContext extends BaseContext
{
    /**
     * id
     * @var long
     */
    protected $id;

    /**
     * 内容
     * @var string
     */
    protected $groupName;
}
