<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Cp\PkPropCardSearchService;

class PkpropcardsearchController extends BaseController
{
    /**
     * @var PkPropCardSearchService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PkPropCardSearchService();
    }

    /**
     * @page pkpropcardsearch
     * @name pk道具卡背包查询
     */
    public function mainAction()
    {
    }

    /**
     * @page  pkpropcardsearch
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page  pkpropcardsearch
     * @point 回收
     * @logRecord(content = '回收', action = '1', model = 'pkpropcardsearch', model_id = 'id')
     */
    public function recoverAction()
    {
        list($flg, $rec) = $this->service->recover($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}