<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoRemoveModifyValidation;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoRemoveValidation;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Service\Operate\Livevideo\LiveVideoService;

class ChatroomremoveController extends BaseController
{
    /**
     * @var LiveVideoService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoService(XsRoomTopConfig::PROPERTY_ROOM_TOP, XsRoomTopConfig::TYPE_REMOVE);
    }
    
    /**
     * @page chatroomremove
     * @name 语音房移除
     */
    public function mainAction()
    {
    }
    
    /**
     * @page chatroomremove
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page chatroomremove
     * @point 移除
     * @logRecord(content = '移除', action = '1', model = 'chatroomremove', model_id = 'id')
     */
    public function removeAction()
    {
        LiveVideoRemoveValidation::make()->validators($this->params);
        $data = $this->service->topAndRemove($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page chatroomremove
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroomremove', model_id = 'id')
     */
    public function modifyAction()
    {
        LiveVideoRemoveModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page chatroomremove
     * @point 取消
     * @logRecord(content = '取消', action = '1', model = 'chatroomremove', model_id = 'id')
     */
    public function cancelAction()
    {
        $data = $this->service->cancel($this->params);
        return $this->outputSuccess($data);
    }
}