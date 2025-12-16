<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Room\RoomRegionSwitchValidation;
use Imee\Service\Operate\Play\Room\LudoSwitchService;

class RoomludoregionswitchController extends BaseController
{
    /** @var LudoSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LudoSwitchService();
    }

    /**
     * @page roomludoregionswitch
     * @name 房间内ludo大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page roomludoregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  roomludoregionswitch
     * @point 修改
     * @logRecord(content = "修改开关", action = "1", model = "roomludoregionswitch", model_id = "id")
     */
    public function modifyAction()
    {
        RoomRegionSwitchValidation::make()->validators($this->params);
        $this->service->modify($this->params);
        return $this->outputSuccess(['id' => $this->params['id'], 'after_json' => $this->params]);
    }
}