<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoListForbiddenValidate;
use Imee\Export\Operate\Chatroom\ChatroomExport;
use Imee\Service\Operate\Chatroom\ChatroomService;
use Imee\Service\Operate\Livevideo\LiveVideoListService;

class ChatroomController extends BaseController
{
    /**
     * @var ChatroomService $service
     */
    private $service;

    /**
     * @var LiveVideoListService $forbiddenService
     */
    private $forbiddenService;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomService();
        $this->forbiddenService = new LiveVideoListService();
    }
    
    /**
     * @page room
     * @name 房间列表
     */
    public function mainAction()
    {
    }
    
    /**
     * @page room
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page room
     * @point 修改聊天室封面
     * @logRecord(content = '修改聊天室封面', action = '1', model = 'room', model_id = 'rid')
     */
    public function coverModifyAction()
    {
        $data = $this->service->coverModify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page room
     * @point 更改名字
     * @logRecord(content = '更改名字', action = '1', model = 'room', model_id = 'rid')
     */
    public function prefixModifyAction()
    {
        $data = $this->service->prefixModify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page room
     * @point 关闭房间
     * @logRecord(content = '关闭房间', action = '1', model = 'room', model_id = 'rid')
     */
    public function closeAction()
    {
        $data = $this->service->close($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page room
     * @point 设置背景图
     * @logRecord(content = '设置背景图', action = '1', model = 'room', model_id = 'rid')
     */
    public function backgroundModifyAction()
    {
        $data = $this->service->backgroundModify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page room
     * @point 封禁
     * @logRecord(content = '封禁', action = '1', model = 'room', model_id = 'id')
     */
    public function forbiddenAction()
    {
        LiveVideoListForbiddenValidate::make()->validators($this->params);
        $data = $this->forbiddenService->forbidden($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page room
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('chatroomExport', ChatroomExport::class, $this->params);
    }
}