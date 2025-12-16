<?php


namespace Imee\Service\Domain\Context\Pretty\Commodity;

use Imee\Service\Domain\Context\PageContext;

/**
 * 商城靓号
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
     * @var string 靓号ID
     */
    protected $prettyUid;

    /**
     * @var string 大区
     */
    protected $supportArea;

    /**
     * @var int 上架状态
     */
    protected $onSaleStatus;

    protected $maxId;
}
