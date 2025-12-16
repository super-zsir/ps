<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Livevideo\LiveVideoListService;

class LivevideolistforbiddenhistoryController extends BaseController
{
    /**
     * @var LiveVideoListService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoListService();
    }
    
    /**
     * @page livevideolistforbiddenhistory
     * @name 封禁记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideolistforbiddenhistory
     * @point 列表
     */
    public function listAction()
    {
        $log = $this->service->getForbiddenLog($this->params);
        return $this->outputSuccess($log);
    }
}