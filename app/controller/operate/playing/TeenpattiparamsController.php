<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\ParamsValidation;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;

class TeenpattiparamsController extends BaseController
{
    /** @var KvBaseService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_TEEN_PATTI_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_TEEN_PATTI,
            '',
            'teenpattiparams'
        );
    }

    /**
     * @page teenpattiparams
     * @name Teen Patti Parameters
     */
    public function mainAction()
    {
    }

    /**
     * @page teenpattiparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getParamsList();
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @page  teenpattiparams
     * @point 修改
     * @logRecord(content = "修改teenpatti参数配置", action = "1", model = "teenpattiparams", model_id = "id")
     */
    public function modifyAction()
    {
        ParamsValidation::make()->validators($this->params);
        $data = $this->service->setParams($this->params);
        return $this->outputSuccess($data);
    }

}