<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Luckyfruit\RegionSwitchService;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;

class LuckyfruitregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }
    
    /**
	 * @page luckyfruitregionswitch
	 * @name Lucky Fruit Region Config
     */
    public function mainAction()
    {
    }

    /**
     * @page luckyfruitregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  luckyfruitregionswitch
     * @point 修改
     * @logRecord(content = "修改Lucky Fruit大区开关", action = "1", model = "luckyfruitregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}