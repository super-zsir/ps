<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Service\Operate\Livevideo\LiveVideoService;

class LivevideoremovehistoryController extends BaseController
{
    /**
     * @var LiveVideoService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoService(XsRoomTopConfig::PROPERTY_ROOM_TOP, XsRoomTopConfig::TYPE_REMOVE);
    }
    
    /**
     * @page livevideoremovehistory
     * @name 操作记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideoremovehistory
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getHistoryListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}