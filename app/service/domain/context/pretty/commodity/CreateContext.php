<?php


namespace Imee\Service\Domain\Context\Pretty\Commodity;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 商城靓号
 */
class CreateContext extends BaseContext
{
    /**
     * @var string 靓号
     */
    protected $prettyUid;

    /**
     * @var int 权重
     */
    protected $weight;

    /**
     * @var string 大区
     */
    protected $supportArea;

    /**
     * @var array 价格信息
     */
    protected $priceInfo;
}
