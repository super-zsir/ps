<?php

namespace Imee\Service\Domain\Service\Csms\Context\Risk;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 敏感词
 */
class SensitiveTextContext extends BaseContext
{
    /**
     * @var string 等检测内容
     */
    protected $content;
}
