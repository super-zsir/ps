<?php


namespace Imee\Service\Domain\Context\Audit\Sensitive;

use Imee\Service\Domain\Context\PageContext;

/**
 * 新版敏感词 列表上下文
 * Class ListContext
 * @package Imee\Service\Domain\Context\Audit\Sensitive
 */
class ListContext extends PageContext
{
    /**
     * @var string 类型
     */
    protected $type;

    /**
     * @var string 二级分类
     */
    protected $subType;

    /**
     * @var string 场景
     */
    protected $cond;

    /**
     * @var int 状态
     */
    protected $deleted;

    /**
     * @var int 是否拼音匹配
     */
    protected $vague;

    /**
     * @var string 敏感词
     */
    protected $text;

    /**
     * @var string 语言
     */
    protected $language;

    /**
     * @var int 危险等级
     */
    protected $danger;
}
