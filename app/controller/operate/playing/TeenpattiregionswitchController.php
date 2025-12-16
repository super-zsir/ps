<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class TeenpattiregionswitchController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_TEEN_PATTI_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_TEEN_PATTI,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'teenpattiregionswitch'
        );
    }

    /**
     * @page teenpattiregionswitch
     * @name Teen Patti Region
     */
    public function mainAction()
    {
    }

    /**
     * @page teenpattiregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  teenpattiregionswitch
     * @point 修改
     * @logRecord(content = "修改teenpatti大区开关", action = "1", model = "teenpattiregionswitch", model_id = "big_area_id")
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->setRegion($this->params);
        return $this->outputSuccess($data);
    }
}