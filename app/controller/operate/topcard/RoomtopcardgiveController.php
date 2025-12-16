<?php


namespace Imee\Controller\Operate\Topcard;


use Imee\Controller\BaseController;
use Imee\Models\Xs\XsSendRoomTopCardLog;
use Imee\Service\Operate\Topcard\RoomTopCardSendService;

class RoomtopcardgiveController extends BaseController
{
    /**
     * @var RoomTopCardSendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomTopCardSendService();
    }

    /**
     * @page roomtopcardgive
     * @name 置顶卡 - 赠送记录
     */
    public function mainAction()
    {
    }

    /**
     * @page roomtopcardgive
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params,XsSendRoomTopCardLog::SOURCE_GIVE);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

}