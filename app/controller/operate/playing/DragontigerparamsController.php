<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Dragontiger\ParamsService;

class DragontigerparamsController extends BaseController
{
    /** @var ParamsService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new ParamsService();
    }

    /**
     * @page dragontigerparams
     * @name Dragon Tiger Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page dragontigerparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  dragontigerparams
     * @point 修改
     * @logRecord(content = "修改Dragon Tiger参数配置", action = "1", model = "dragontigerparams", model_id = "id")
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

}