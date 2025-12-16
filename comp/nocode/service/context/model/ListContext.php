<?php

namespace Imee\Comp\Nocode\Service\Context\Model;

use Imee\Comp\Nocode\Service\Context\BaseContext;

class ListContext extends BaseContext
{
    /**
     * @var string 模型名称
     */
    public $name;

    /**
     * @var int 页码
     */
    public $page;

    /**
     * @var int 每页数量
     */
    public $limit;
}

