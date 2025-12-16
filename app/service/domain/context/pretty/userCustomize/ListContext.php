<?php


namespace Imee\Service\Domain\Context\Pretty\UserCustomize;

use Imee\Service\Domain\Context\PageContext;

/**
 * 靓号发放管理
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    /**
     * @var int id
     */
    protected $id;

    /**
     * @var int uid
     */
    protected $uid;

    /**
     * @var int 类型ID
     */
    protected $customizePrettyId;

    /**
     * @var int 状态
     */
    protected $status;

    /**
     * @var date 起始时间
     */
    protected $createDatelineSdate;

    /**
     * @var date 结束时间
     */
    protected $createDatelineEdate;

    protected $maxId;

    protected $giveType;

    protected $source;

    protected $sourceId;

}
