<?php

namespace Imee\Service\Domain\Service\Abstracts;

use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Traits\JoinTrait;

abstract class MongoListAbstract
{
    use JoinTrait;

    protected $masterClass;
    protected $query;
    protected $context;
    protected $where = [];

    public function __construct(PageContext $context)
    {
        $this->context = $context;
    }

    /**
     *
     * $aggregation = [
     * 'user_detail' => [
     * '$lookup' => [
     * 'from' => 'user_detail',
     * 'localField' => '_id',
     * 'foreignField' => '_id',
     * 'as' => 'user_detail'
     * ]
     * ],
     * 'login' => [
     * '$lookup' => [
     * 'from' => 'login',
     * 'localField' => '_id',
     * 'foreignField' => '_id',
     * 'as' => 'login'
     * ]
     * ],
     * 'count' => [
     * '$count' => 'count'
     * ]
     * ];
     * @return mixed
     */
    abstract protected function buildWhere();

    protected function getPaginatorModel()
    {
        $returnRes = [
            'total' => 0,
            'items' => null,
        ];

        $this->where = (new $this->masterClass)->fieldConvert($this->where);

        $total = $this->getCount();

        if ($total == 0) {
            return $returnRes;
        }

        $order  = ['order' => $this->getOrderBy()];
        $limit  = ['limit' => $this->getLimit()];
        $offset = ['offset' => $this->getOffset()];

        $parameters = array_merge($this->where, $order, $limit, $offset);

        $returnRes['total'] = $total;
        $returnRes['items'] = $this->getList($parameters);

        return $returnRes;
    }

    public function handle()
    {
        $returnRes = [
            'total' => 0,
            'data'  => [],
        ];

        $this->buildWhere();
        $this->join();

        $paginatorModel = $this->getPaginatorModel();

        $total = $paginatorModel['total'];
        $items = $paginatorModel['items'];

        if ($total == 0) {
            return $returnRes;
        }
        $res = $this->formatList($items);

        $returnRes['data']  = $res;
        $returnRes['total'] = $total;
        return $returnRes;
    }

    abstract protected function formatList($items);

    protected function getCount()
    {
        return $this->masterClass::aggCount($this->where);
    }

    protected function getList($parameters)
    {
        return $this->masterClass::aggFind($parameters);
    }

    protected function getOrderBy()
    {
        return [$this->context->sort => $this->context->dir];
    }

    protected function getLimit()
    {
        return $this->context->limit;
    }

    protected function getOffset()
    {
        return $this->context->offset;
    }

    /**
     * 格式化数据
     * @param $items
     * @return array
     */
    protected static function formatAgg($items)
    {
        $list = [];

        if (empty($items)) {
            return [];
        }

        foreach ($items as $item) {
            $tmp    = json_decode(json_encode($item), true);
            $list[] = $tmp;
        }

        return $list;
    }
}
