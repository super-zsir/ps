<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomAdminAddValidation;
use Imee\Controller\Validation\Operate\Chatroom\ChatroomAdminEditValidation;
use Imee\Service\Operate\Chatroom\ChatroomAdminService;

class ChatroomadminController extends BaseController
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
     * @page chatroomadmin
     * @name 管理员
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroomadmin
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page chatroomadmin
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'chatroomadmin', model_id = 'id')
     */
    public function createAction()
    {
        ChatroomAdminAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page chatroomadmin
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroomadmin', model_id = 'id')
     */
    public function modifyAction()
    {
        ChatroomAdminEditValidation::make()->validators($this->params);
        $data = $this->service->save($this->params);
        return $this->outputSuccess($data);
    }
}