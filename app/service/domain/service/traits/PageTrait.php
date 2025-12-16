<?php

namespace Imee\Service\Domain\Service\Traits;

trait PageTrait
{
    protected $page = 1;

    protected $limit = 15;

    protected $sort = ''; // 排序字段

    protected $dir = ''; // 降序或升序

    protected $orderBy = '';

    protected $offset;

    protected $params;

    protected function setListParams()
    {
        $this->page    = $this->params['page'] ?? 1;
        $this->limit   = $this->params['limit'] ?? 15;
        $this->offset  = $this->params['offset'] ?? (($this->page - 1) * $this->limit);
        $this->sort    = $this->params['sort'] ?? '';
        $this->dir     = $this->params['dir'] ?? '';
        $this->orderBy = $this->params['orderBy'] ?? $this->sort . ' ' . $this->dir;
    }

    protected function addDefaultOrder($sort, $dir)
    {
        if (empty($this->sort) || empty($this->dir) || empty($this->orderBy)) {
            $this->sort    = $sort;
            $this->dir     = $dir;
            $this->orderBy = $sort . ' ' . $dir;
        }
    }
}
