<?php

namespace Imee\Service\Domain\Context;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 带页码列表上下文
 */
class PageContext extends BaseContext
{
    public function __construct(array $conditions)
    {
        $page = $conditions['page'] ?? 1;
        $limit = $conditions['limit'] ?? ($this->limit ?? 15);
        $conditions['offset'] = ($page - 1) * $limit;
        parent::__construct($conditions);
    }

    protected $page = 1;

    protected $limit;

    protected $sort;

    protected $dir;

    protected $offset;
}
