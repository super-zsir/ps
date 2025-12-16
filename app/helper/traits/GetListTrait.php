<?php

namespace Imee\Helper\Traits;

use Imee\Service\Lesscode\ModelSupportService;

trait GetListTrait
{
    protected function getList($conditions, $fields = null, $orderBy = null, $pageSize = null): \Generator
    {
        !isset($conditions['_model']) && $conditions['_model'] = $this->masterModel;

        $fields  = is_null($fields)  ? (!property_exists($this, 'fields') || is_null($this->fields)  ? '*' : $this->fields) : $fields;
        $orderBy = is_null($orderBy) ? (!property_exists($this, 'orderBy') || is_null($this->orderBy) ? 'id asc' : $this->orderBy) : $orderBy;

        $pageNo = 1;
        $pageSize = is_null($pageSize) ? (!property_exists($this, 'pageSize') || is_null($this->pageSize) ? 1000 : $this->pageSize) : $pageSize;

        while (true) {
            $list = ModelSupportService::getList($conditions, $fields, $orderBy, $pageNo, $pageSize);

            ++$pageNo;

            if (!$list->valid()) {
                break;
            }

            yield $list;
        }
    }

    protected function getListByPk($conditions, $pkField = 'id', $fields = null, $orderBy = null, $pageSize = null): \Generator
    {
        !isset($conditions['_model']) && $conditions['_model'] = $this->masterModel;

        $fields  = is_null($fields)  ? (!property_exists($this, 'fields') || is_null($this->fields)  ? '*' : $this->fields) : $fields;
        $orderBy = is_null($orderBy) ? (!property_exists($this, 'orderBy') || is_null($this->orderBy) ? 'id asc' : $this->orderBy) : $orderBy;

        $pageNo = 1;
        $pageSize = is_null($pageSize) ? (!property_exists($this, 'pageSize') || is_null($this->pageSize) ? 1000 : $this->pageSize) : $pageSize;

        $lastId = 0;

        while (true) {

            if ($lastId > 0) {
                $conditions[$pkField] = ['gt', $lastId];
            }

            $list = ModelSupportService::getList($conditions, $fields, $orderBy, $pageNo, $pageSize);

            if (!$list->valid()) {
                break;
            }

            $listArr = $list->toArray();
            $lastId = end($listArr)[$pkField];
            unset($listArr);

            yield $list;
        }
    }

    protected function getListByPkDesc($conditions, $pkField = 'id', $fields = null, $orderBy = null, $pageSize = null): \Generator
    {
        !isset($conditions['_model']) && $conditions['_model'] = $this->masterModel;

        $fields  = is_null($fields)  ? (!property_exists($this, 'fields') || is_null($this->fields)  ? '*' : $this->fields) : $fields;
        $orderBy = is_null($orderBy) ? (!property_exists($this, 'orderBy') || is_null($this->orderBy) ? 'id asc' : $this->orderBy) : $orderBy;

        $pageNo = 1;
        $pageSize = is_null($pageSize) ? (!property_exists($this, 'pageSize') || is_null($this->pageSize) ? 1000 : $this->pageSize) : $pageSize;

        $lastId = 0;

        while (true) {

            if ($lastId > 0) {
                $conditions[$pkField] = ['lt', $lastId];
            }

            $list = ModelSupportService::getList($conditions, $fields, $orderBy, $pageNo, $pageSize);

            if (!$list->valid()) {
                break;
            }

            $listArr = $list->toArray();
            $lastId = end($listArr)[$pkField];
            unset($listArr);

            yield $list;
        }
    }
}
