<?php


namespace Imee\Service\Domain\Service\Csms\Context\Saas;

use Imee\Service\Domain\Context\BaseContext;

class InitDataContext extends BaseContext
{
    /**
     * @var string 审核项
     */
    protected $choice;

    protected $type;

    /**
     * @var string 表名
     */
    protected $table;

    /**
     * @var string 库名
     */
    protected $db;

    /**
     * @var string 主键ID
     */
    protected $pkField;

    /**
     * @var string uid所在此表中对应的字段
     */
    protected $uid;

    /**
     * @var FieldContext[] 数据表字段
     */
    protected $fieldContexts;

    /**
     * @var int 先发后审/先审后发
     */
    protected $review;

    /**
     * @var string 唯一标识
     */
    protected $taskid;

    /**
     * @var int 优先级
     */
    protected $level;

    /**
     * @var int app_id
     */
    protected $appId;

    /**
     * @var int 先审后发的命中的风控ID
     */
    protected $strategy;


	/**
	 * @var int 原始表主键ID
	 */
    protected $pkValue;

	protected $sex;

    protected $extra;

}
