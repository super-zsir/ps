<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Chatroom\ChatroomBackgroundTypeService;

class ChatroombackgroundtypehistoryController extends BaseController
{
    /**
     * @var ChatroombackgroundTypeService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroombackgroundTypeService();
    }
    
    /**
     * @page chatroombackgroundtypehistory
     * @name 历史操作记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroombackgroundtypehistory
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getHistoryListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}