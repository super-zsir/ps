<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingParamsValidation;
use Imee\Service\Operate\Play\Fishing\FishingParamsService;

class FishingparametersController extends BaseController
{
    /** @var FishingParamsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FishingParamsService();
    }

    /**
     * @page fishingparameters
     * @name Fishing Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page fishingparameters
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  fishingparameters
     * @point 修改
     * @logRecord(content = "修改fishing参数配置", action = "1", model = "fishingparameters", model_id = "id")
     */
    public function modifyAction()
    {
        FishingParamsValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

}