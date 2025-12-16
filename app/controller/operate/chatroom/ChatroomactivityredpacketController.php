<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomActivityRedPacketAddValidation;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomActivityRedPacketEditValidation;
use Imee\Service\Operate\Chatroom\ChatroomActivityRedPacketService;

class ChatroomactivityredpacketController extends BaseController
{
    /**
     * @var ChatroomActivityRedPacketService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomActivityRedPacketService();
    }
    
    /**
     * @page chatroomactivityredpacket
     * @name 设置红包图
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroomactivityredpacket
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page chatroomactivityredpacket
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'chatroomactivityredpacket', model_id = 'id')
     */
    public function createAction()
    {
        ChatroomActivityRedPacketAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page chatroomactivityredpacket
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroomactivityredpacket', model_id = 'id')
     */
    public function modifyAction()
    {
        ChatroomActivityRedPacketEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}