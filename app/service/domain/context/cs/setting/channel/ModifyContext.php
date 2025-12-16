<?php

namespace Imee\Service\Domain\Context\Cs\Setting\Channel;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 通道修改
 */
class ModifyContext extends BaseContext
{
    /**
     * ID
     * @var integer
     */
    protected $id;

    /**
     * 语言
     * @var array
     */
    protected $language;
}
