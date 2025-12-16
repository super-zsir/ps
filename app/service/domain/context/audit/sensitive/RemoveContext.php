<?php


namespace Imee\Service\Domain\Context\Audit\Sensitive;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 新版敏感词 删除上下文
 * Class RemoveContext
 * @package Imee\Service\Domain\Context\Audit\Sensitive
 */
class RemoveContext extends BaseContext
{
    /**
     * @var string 敏感词
     */
    protected $text;
}
