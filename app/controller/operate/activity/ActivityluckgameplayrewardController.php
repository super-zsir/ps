<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Activity\ActivityLuckGamePlayService;

class ActivityluckgameplayrewardController extends BaseController
{
    /**
     * @var ActivityLuckGamePlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityLuckGamePlayService();
    }
    
    /**
     * @page activityluckgameplayreward
     * @name 库存变更日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page activityluckgameplayreward
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getStockRecordList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
}