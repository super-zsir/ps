<?php

namespace Imee\Comp\Nocode\Service\Context\Form;

use Imee\Comp\Nocode\Service\Context\BaseContext;

class ListContext extends BaseContext
{
    /**
     * @var string 标识
     */
    public $ncid;

    /**
     * @var int 页码
     */
    public $page;

    /**
     * @var int 每页数量
     */
    public $limit;
}

