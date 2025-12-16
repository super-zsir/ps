<?php

namespace Imee\Service\Domain\Context\Cs\Setting\QuickReply;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 快捷回复修改上下文
 */
class ModifyContext extends BaseContext
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
    protected $content;
}
