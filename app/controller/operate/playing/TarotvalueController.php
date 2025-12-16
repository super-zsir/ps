<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\ValueAddValidation;
use Imee\Controller\Validation\Operate\Play\Tarot\ValueEditValidation;
use Imee\Service\Operate\Play\Tarot\ValueService;

class TarotvalueController extends BaseController
{
    /**
     * @var ValueService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ValueService();
    }
    
    /**
     * @page tarotvalue
     * @name Tarot Value
     */
    public function mainAction()
    {
    }
    
    /**
     * @page tarotvalue
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }
    
    /**
     * @page tarotvalue
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'tarotvalue', model_id = 'id')
     */
    public function createAction()
    {
        ValueAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page tarotvalue
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'tarotvalue', model_id = 'id')
     */
    public function modifyAction()
    {
        ValueEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}