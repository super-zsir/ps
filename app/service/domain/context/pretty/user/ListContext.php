<?php


namespace Imee\Service\Domain\Context\Pretty\User;

use Imee\Service\Domain\Context\PageContext;

/**
 * 靓号管理
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    /**
     * @var int uid
     */
    protected $uid;

    /**
     * @var string 靓号
     */
    protected $prettyUid;

    /**
     * @var int 来源
     */
    protected $prettySource;

    /**
     * @var int 状态
     */
    protected $status;

    /**
     * @var date 起始时间
     */
    protected $datelineSdate;

    /**
     * @var date 结束时间
     */
    protected $datelineEdate;

    protected $maxId;
}
