<?php

namespace Imee\Controller\Operate\Commodity;

use Imee\Controller\BaseController;
use Imee\Service\Commodity\ChatroomHeadPicService;

class ChatroomheadpicController extends BaseController
{

    /**
     * @var ChatroomHeadPicService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomHeadPicService();
    }

    /**
     * @page chatroomheadpic
     * @name 运营系统-物品管理-用户房间头像框
     */
    public function mainAction()
    {
    }

    /**
     * @page chatroomheadpic
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  chatroomheadpic
     * @point create
     * @logRecord(content = "创建", action = "0", model = "XsChatroomHeadpic", model_id = "id")
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  chatroomheadpic
     * @point modify
     * @logRecord(content = "修改", action = "1", model = "XsChatroomHeadpic", model_id = "id")
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  chatroomheadpic
     * @point delete
     * @logRecord(content = "删除", action = "2", model = "XsChatroomHeadpic", model_id = "id")
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}