<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Chatroom\ChatroomAdminService;

class ChatroomadminhistoryController extends BaseController
{
    /**
     * @var ChatroomAdminService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomAdminService();
    }
    
    /**
     * @page chatroomadminhistory
     * @name 管理员记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroomadminhistory
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getHistoryListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}