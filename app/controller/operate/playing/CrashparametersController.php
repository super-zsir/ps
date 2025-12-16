<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Crash\ParamsService;

class CrashparametersController extends BaseController
{
    /** @var ParamsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ParamsService();
    }

    /**
     * @page crashparameters
     * @name Crash Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page crashparameters
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  crashparameters
     * @point 修改
     * @logRecord(content = "修改crash参数配置", action = "1", model = "crashparameters", model_id = "id")
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

}