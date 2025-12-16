<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Crash\RegionSwitchService;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;


class CrashregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page crashregionswitch
     * @name Crash Region
     */
    public function mainAction()
    {
    }

    /**
     * @page crashregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  crashregionswitch
     * @point 修改
     * @logRecord(content = "修改crash大区开关", action = "1", model = "crashregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}