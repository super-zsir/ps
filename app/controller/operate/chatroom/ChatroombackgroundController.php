<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomBackgroundAddValidation;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomBackgroundEditValidation;
use Imee\Service\Operate\Chatroom\ChatroomBackgroundService;

class ChatroombackgroundController extends BaseController
{
    /**
     * @var ChatroomBackgroundService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomBackgroundService();
    }
    
    /**
     * @page chatroombackground
     * @name 背景图管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroombackground
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page chatroombackground
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'chatroombackground', model_id = 'id')
     */
    public function createAction()
    {
        ChatroomBackgroundAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page chatroombackground
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroombackground', model_id = 'id')
     */
    public function modifyAction()
    {
        ChatroomBackgroundEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}