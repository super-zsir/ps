<?php


namespace Imee\Service\Domain\Context\Pretty\Commodity;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 商城靓号
 */
class ModifyContext extends CreateContext
{
    /**
     * @var int id
     */
    protected $id;

    /**
     * @var int 上架状态
     */
    protected $onSaleStatus;
}
