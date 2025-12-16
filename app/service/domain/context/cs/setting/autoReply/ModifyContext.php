<?php

namespace Imee\Service\Domain\Context\Cs\Setting\AutoReply;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 角色创建
 */
class ModifyContext extends BaseContext
{
    /**
     * id
     * @var integer
     */
    protected $id;

    /**
     * 标题
     * @var string
     */
    protected $subject;

    /**
     * 问题类型
     * @var integer
     */
    protected $type;

    /**
     * 标签
     * @var string
     */
    protected $tag;

    /**
     * 回答
     * @var string
     */
    protected $answer;

    /**
     * 引导转人工
     * @var integer
     */
    protected $guideToService;

    /**
     * 排序
     * @var integer
     */
    protected $hot;

	/**
	 * 语言
	 * @var string
	 */
	protected $language;
}
