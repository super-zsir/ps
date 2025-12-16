<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class PslotregionswitchController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_NEW_SLOT_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_NEW_SLOT,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'pslotregionswitch'
        );
    }

    /**
     * @page pslotregionswitch
     * @name Greedyslot Region
     */
    public function mainAction()
    {
    }

    /**
     * @page pslotregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  pslotregionswitch
     * @point 修改
     * @logRecord(content = "修改pslot大区开关", action = "1", model = "pslotregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->setRegion($this->params);
        return $this->outputSuccess($data);
    }
}