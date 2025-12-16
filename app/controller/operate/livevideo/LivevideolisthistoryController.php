<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsBmsVideoLiveStopLog;
use Imee\Service\Operate\Livevideo\LiveVideoListService;

class LivevideolisthistoryController extends BaseController
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
     * @page livevideolisthistory
     * @name 结束详情
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideolisthistory
     * @point 列表
     */
    public function listAction()
    {
        $this->params['type'] = XsBmsVideoLiveStopLog::TYPE_VIDEO_LIVE;
        $list = $this->service->getHistoryListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}