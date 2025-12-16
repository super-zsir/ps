<?php

namespace Imee\Controller\Operate\Chatroom;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoTopModifyValidation;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoTopValidation;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Service\Operate\Livevideo\LiveVideoService;

class ChatroomtopController extends BaseController
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
     * @page chatroomtop
     * @name 语音房置顶
     */
    public function mainAction()
    {
    }

    /**
     * @page chatroomtop
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page chatroomtop
     * @point 置顶
     * @logRecord(content = '置顶', action = '1', model = 'chatroomtop', model_id = 'id')
     */
    public function topAction()
    {
        LiveVideoTopValidation::make()->validators($this->params);
        $data = $this->service->topAndRemove($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page chatroomtop
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroomtop', model_id = 'id')
     */
    public function modifyAction()
    {
        LiveVideoTopModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page chatroomtop
     * @point 取消
     * @logRecord(content = '取消', action = '1', model = 'chatroomtop', model_id = 'id')
     */
    public function cancelAction()
    {
        $data = $this->service->cancel($this->params);
        return $this->outputSuccess($data);
    }
}