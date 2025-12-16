<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Chatroom\ChatroomPublicScreenMsgService;

class ChatroompublicscreenmsgController extends BaseController
{
    /**
     * @var ChatroomPublicScreenMsgService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomPublicScreenMsgService();
    }
    
    /**
     * @page chatroompublicscreenmsg
     * @name 公屏消息
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroompublicscreenmsg
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}