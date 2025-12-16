<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Service\Operate\Livevideo\LiveVideoService;

class ChatroomtophistoryController extends BaseController
{
    /**
     * @var LiveVideoService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoService(XsRoomTopConfig::PROPERTY_ROOM_TOP, XsRoomTopConfig::TYPE_TOP);
    }
    
    /**
     * @page chatroomtophistory
     * @name 操作记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroomtophistory
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getHistoryListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}