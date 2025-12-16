<?php


namespace Imee\Service\Domain\Service\Csms\Context\Staff;

use Imee\Service\Domain\Context\PageContext;

class TextMachineListContext extends PageContext
{

    /**
     * 起始时间
     */
    protected $beginTime;

    /**
     * 结束时间
     */
    protected $endTime;

    /**
     * 审批状态
     */
    protected $deleted;

    /**
     * 复审审批状态
     */
    protected $deleted2;


    protected $reason2;

    /**
     * 是否是复审页面
     */
    protected $isSecond = false;

    /**
     * 表名
     */
    protected $table;

    /**
     * 表名的主键
     */
    protected $pkValue;

    /**
     * 原因
     */
    protected $reason;

    /**
     * 性别
     */
    protected $sex;

    protected $appIds = [];

    protected $ids = [];

    /**
     * 终审状态
     */
    protected $deleted3;

    /**
     * 是否是终审
     */
    protected $isFinal = false;

    /**
     * 用户提交内容
     * @var string
     */
    protected $content;

    protected $sort = 'id';

    protected $dir = 'desc';

    public $page = 1;

    public $limit = 30;

    public $debug;

	// 根据审核项搜索
    public $choice;

    public $type;

    // 审核项数组
    public $choices;

    public $language;

    public $area;

    public $uid;

    public $machine;
}
