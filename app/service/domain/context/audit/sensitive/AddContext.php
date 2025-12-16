<?php


namespace Imee\Service\Domain\Context\Audit\Sensitive;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 新版敏感词 新增上下文
 * Class AddContext
 * @package Imee\Service\Domain\Context\Audit\Sensitive
 */
class AddContext extends BaseContext
{
    /**
     * @var string 类型
     */
    protected $type;

    /**
     * @var string 二级要类
     */
    protected $subType;

    /**
     * @var array 场景
     */
    protected $cond;

    /**
     * @var int 是否拼音匹配
     */
    protected $vague;

    /**
     * @var int 危险等级
     */
    protected $danger;

    /**
     * @var int 是否精准匹配
     */
    protected $accurate;

    /**
     * @var string 语言
     */
    protected $language;

    /**
     * @var array 敏感词
     */
    protected $text;

    /**
     * @var string 原因
     */
    protected $reason;
}
