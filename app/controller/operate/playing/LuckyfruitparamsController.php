<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Luckyfruit\ParamsService;

class LuckyfruitparamsController extends BaseController
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
     * @page luckyfruitparams
     * @name Lucky Fruit Parameters Config
     */
    public function mainAction()
    {
    }

    /**
     * @page luckyfruitparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  luckyfruitparams
     * @point 编辑
     */
    public function modifyTotalAction()
    {
        $data = $this->service->modifyTotal($this->params);
        return $this->outputSuccess($data);
    }
}