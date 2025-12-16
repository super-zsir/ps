<?php


namespace Imee\Service\Domain\Context\Audit\Sensitive;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 新版敏感词 修改上下文
 * Class ModifyContext
 * @package Imee\Service\Domain\Context\Audit\Sensitive
 */
class ModifyContext extends AddContext
{
    /**
     * @var int ID
     */
    protected $id;

    /**
     * @var int 状态
     */
    protected $deleted;
}
