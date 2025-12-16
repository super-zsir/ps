<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\ParamsValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class TarotparamsController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_TAROT_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_TAROT,
            '',
            'tarotparams'
        );
    }

    /**
     * @page tarotparams
     * @name Tarot Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page tarotparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getParamsList();
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @page  tarotparams
     * @point 修改
     * @logRecord(content = "修改tarot参数配置", action = "1", model = "tarotparams", model_id = "id")
     */
    public function modifyAction()
    {
        ParamsValidation::make()->validators($this->params);
        $data = $this->service->setParams($this->params);
        return $this->outputSuccess($data);
    }

}