<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\ParamsValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class GreedyboxplayparamsController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_GREEDY_BOX_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_GREEDY_BOX,
            '',
            'greedyboxplayparams'
        );
    }

    /**
     * @page greedyboxplayparams
     * @name Greedy Box Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyboxplayparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getParamsList();
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @page  greedyboxplayparams
     * @point 修改
     * @logRecord(content = "修改greedybox参数配置", action = "1", model = "greedyboxplayparams", model_id = "id")
     */
    public function modifyAction()
    {
        ParamsValidation::make()->validators($this->params);
        $data = $this->service->setParams($this->params);
        return $this->outputSuccess($data);
    }

}