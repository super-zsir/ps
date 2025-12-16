<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\ParamsValidation;
use Imee\Service\Operate\Play\Horserace\ParamsService;

class HorseraceparamsController extends BaseController
{
    /** @var ParamsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ParamsService();
    }

    /**
     * @page horseraceparams
     * @name Horse Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page horseraceparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page horseraceparams
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'horseraceparams', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}