<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomBackgroundTypeAddValidation;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomBackgroundTypeEditValidation;
use Imee\Service\Operate\Chatroom\ChatroomBackgroundTypeService;

class ChatroombackgroundtypeController extends BaseController
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
     * @page chatroombackgroundtype
     * @name 图片管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroombackgroundtype
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page chatroombackgroundtype
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'chatroombackgroundtype', model_id = 'id')
     */
    public function createAction()
    {
        ChatroomBackgroundTypeAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page chatroombackgroundtype
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroombackgroundtype', model_id = 'id')
     */
    public function modifyAction()
    {
        ChatroomBackgroundTypeEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}