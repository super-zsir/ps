<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Horserace\HorseRaceWeightValidation;
use Imee\Service\Operate\Play\Horserace\WeightService;

class HorseraceweightController extends BaseController
{
    /** @var WeightService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WeightService();
    }

    /**
     * @page horseraceweight
     * @name Horse Percent
     */
    public function mainAction()
    {
    }

    /**
     * @page horseraceweight
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page horseraceweight
     * @point 编辑
     * @logRecord(content = '修改', action = '1', model = 'horseraceweight', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}