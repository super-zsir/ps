<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Room\RoomRegionSwitchValidation;
use Imee\Service\Operate\Play\Room\BilliardSwitchService;

class RoombilliardregionswitchController extends BaseController
{
    /** @var BilliardSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BilliardSwitchService();
    }

    /**
     * @page roombilliardregionswitch
     * @name 房间内billiard大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page roombilliardregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  roombilliardregionswitch
     * @point 修改
     * @logRecord(content = "修改开关", action = "1", model = "roombilliardregionswitch", model_id = "id")
     */
    public function modifyAction()
    {
        RoomRegionSwitchValidation::make()->validators($this->params);
        $this->service->modify($this->params);
        return $this->outputSuccess(['id' => $this->params['id'], 'after_json' => $this->params]);
    }
}