<?php

namespace Imee\Service\Domain\Service\Abstracts;

use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Traits\JoinTrait;
use Imee\Service\Domain\Service\Traits\EsTrait;

abstract class NormalListAbstract
{
    use JoinTrait, EsTrait;
    protected $masterClass;
    protected $query;
    protected $context;
    protected $where = ['condition' => [], 'bind' => []];
    protected $esWhere = [];
    protected $dataFrom = 'mysql';
    protected $leftClass;

    public function __construct(PageContext $context)
    {
        $this->context = $context;
    }

    abstract protected function buildWhere();

    protected function getPaginatorModel()
    {
        $returnRes = [
            'total' => 0,
            'items' => null,
        ];
        if (!empty($this->where['condition'])) {
            $this->query->where(implode(" and ", $this->where['condition']));
            $this->query->bind($this->where['bind']);
        }

        if ($this->leftClass) {
            $this->join();
        }

        $count_query = clone $this->query;
        $total = $count_query->columns("count(*) as total")->execute()->getFirst();
        if (!$total) {
            return $returnRes;
        }

        $this->setColumns();

        if (!empty($this->context->sort) && !empty($this->context->dir)) {
            $this->query->orderBy("$this->masterClass.".$this->context->sort.' '. $this->context->dir);
        }

        $groupBy = $this->getGroupBy();
        if (!empty($groupBy)) {
            $this->query->groupBy($groupBy);
        }

        $this->query->limit($this->context->limit, $this->context->offset);

        $result = $this->query->execute();

        $returnRes = [
            'total' => $total->total,
            'items' => $result,
        ];
        return $returnRes;
    }

    public function handle()
    {
        $returnRes = [
            'total' => 0,
            'data' => [],
        ];
        $this->buildWhere();

        if ($this->dataFrom == 'mysql') {
            $paginatorModel = $this->getPaginatorModel();
        
            $total = $paginatorModel['total'];
            $items = $paginatorModel['items'];
        } else {
            $esModel = $this->getEsModel();
            $total = $esModel['total'];
            $items = $esModel['items'];
        }

        if ($total == 0) {
            return $returnRes;
        }
        $res = $this->formatList($items);

        $returnRes['data'] = $res;
        $returnRes['total'] = $total;
        return $returnRes;
    }

    protected function getGroupBy()
    {
        return '';
    }

    abstract protected function formatList($items);
}
