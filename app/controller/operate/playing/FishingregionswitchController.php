<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class FishingregionswitchController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_FISHING_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_FISHING,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'fishingregionswitch'
        );
    }

    /**
     * @page fishingregionswitch
     * @name Fishing Region
     */
    public function mainAction()
    {
    }

    /**
     * @page fishingregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  fishingregionswitch
     * @point 修改
     * @logRecord(content = "修改fishing大区开关", action = "1", model = "fishingregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->setRegion($this->params);
        return $this->outputSuccess($data);
    }
}