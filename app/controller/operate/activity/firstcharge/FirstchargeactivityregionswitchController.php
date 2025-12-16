<?php

namespace Imee\Controller\Operate\Activity\Firstcharge;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Activity\Firstcharge\RegionSwitchService;

class FirstchargeactivityregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page firstchargeActivityregionswitch
     * @name 首充活动大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page firstchargeActivityregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  firstchargeActivityregionswitch
     * @point 修改
     * @logRecord(content = "修改首充活动大区开关", action = "1", model = "firstchargeActivityregionswitch", model_id = "id")
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}