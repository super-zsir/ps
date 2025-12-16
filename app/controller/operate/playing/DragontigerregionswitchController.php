<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;
use Imee\Service\Operate\Play\Dragontiger\RegionSwitchService;

class DragontigerregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }
    
    /**
	 * @page dragontigerregionswitch
	 * @name Dragon Tiger Region
	 */
    public function mainAction()
    {
    }

    /**
     * @page dragontigerregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  dragontigerregionswitch
     * @point 修改
     * @logRecord(content = "修改Dragon Tiger大区开关", action = "1", model = "dragontigerregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}