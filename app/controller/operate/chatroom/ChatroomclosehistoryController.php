<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsBmsVideoLiveStopLog;
use Imee\Service\Operate\Livevideo\LiveVideoListService;

class ChatroomclosehistoryController extends BaseController
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
     * @page chatroomclosehistory
     * @name 关闭记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroomclosehistory
     * @point 列表
     */
    public function listAction()
    {
        $this->params['type'] = XsBmsVideoLiveStopLog::TYPE_ROOM;
        $list = $this->service->getHistoryListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}