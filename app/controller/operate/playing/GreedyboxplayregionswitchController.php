<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Operate\Playing\Teen;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class GreedyboxplayregionswitchController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_GREEDY_BOX_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_GREEDY_BOX,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'greedyboxplayregionswitch'
        );
    }

    /**
     * @page greedyboxplayregionswitch
     * @name Greedy Box Region
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyboxplayregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  greedyboxplayregionswitch
     * @point 修改
     * @logRecord(content = "修改greedybox大区开关", action = "1", model = "greedyboxplayregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->setRegion($this->params);
        return $this->outputSuccess($data);
    }
}