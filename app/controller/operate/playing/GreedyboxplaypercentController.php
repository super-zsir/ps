<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsUidGameBlackList;
use Imee\Service\Operate\Play\Greedybox\OddsService;

class GreedyboxplaypercentController extends BaseController
{
    /**
     * @var OddsService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OddsService(XsUidGameBlackList::GREEDY_BOX);
    }
    
    /**
     * @page greedyboxplaypercent
     * @name Greedy Box Percent
     */
    public function mainAction()
    {
    }
    
    /**
     * @page greedyboxplaypercent
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page greedyboxplaypercent
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedyboxplaypercent', model_id = 'item_id')
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}