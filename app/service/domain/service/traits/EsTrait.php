<?php

namespace Imee\Service\Domain\Service\Traits;

trait EsTrait
{
    protected $esWhere = [];
    protected $esPrimaryKey = 'id';
    protected $esClass;
    protected $esSqlExtends = [];
    protected $esKeys = [];
    private function getEsModel()
    {
        $return = ['total' => 0, 'items' => null];
        $model = new $this->esClass;
        $esSql = $model->createSearchByWhere($this->esWhere);

        $esSql = array_merge($esSql, $this->esSqlExtends);

        $esSearch = $model::searchByQuery(
            $esSql,
            null,
            null,
            $this->context->limit,
            $this->context->offset,
            [$this->context->sort => $this->context->dir]
        );

        $total = $esSearch->totalHits();
        $return['total'] = $total;
        if (!$total) {
            return $return;
        }

        $hits = $esSearch->getHits()['hits'];
        $esIds = array_map(function ($mdata) {
            return $mdata['_source'][$this->esPrimaryKey];
        }, $hits);

        if (!empty($esIds)) {
            $this->esKeys = $esIds;
            $return['items'] = $this->getEsResult();
        }

        return $return;
    }

    protected function getEsResult()
    {
        return null;
    }
}
