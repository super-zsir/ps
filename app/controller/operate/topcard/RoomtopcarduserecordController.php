<?php

namespace Imee\Controller\Operate\Topcard;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Topcard\RoomTopCardUseRecordService;

class RoomtopcarduserecordController extends BaseController
{
    /**
     * @var RoomTopCardUseRecordService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomTopCardUseRecordService();
    }
    
    /**
     * @page roomtopcarduserecord
     * @name 置顶卡使用记录
     */
    public function mainAction()
    {
    }

    /**
     * @page roomtopcarduserecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}