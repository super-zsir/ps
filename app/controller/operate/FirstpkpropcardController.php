<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\FirstPkPropCardValidation;
use Imee\Service\Operate\Play\FirstPkPropCardService;

class FirstpkpropcardController extends BaseController
{
    /**
     * @var FirstPkPropCardService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FirstPkPropCardService();
    }

    /**
     * @page firstpkpropcard
     * @name 首充pk道具管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  firstpkpropcard
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $list = $this->service->getListAndTotal($params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page  firstpkpropcard
     * @point 首充pk道具管理配置
     * @logRecord(content = '首充pk道具管理', action = '1', model = 'firstpkpropcard', model_id = 'big_area_id')
     */
    public function configAction()
    {
        FirstPkPropCardValidation::make()->validators($this->params);
        $data = $this->service->config($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page  firstpkpropcard
     * @point 首充pk道具管理详情
     */
    public function infoAction()
    {
        return $this->outputSuccess($this->service->info($this->params));
    }
}