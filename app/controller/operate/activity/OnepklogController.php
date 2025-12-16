<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Activity\ActivityOnePkPlayService;

class OnepklogController extends BaseController
{
    /**
     * @var ActivityOnePkPlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityOnePkPlayService();
    }
    
    /**
     * @page onepklog
     * @name 对战操作日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page onepklog
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getLogList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}