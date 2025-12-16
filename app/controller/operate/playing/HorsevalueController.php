<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Horse\ValueAddValidation;
use Imee\Controller\Validation\Operate\Play\Horse\ValueEditValidation;
use Imee\Service\Operate\Play\Horserace\ValueService;

class HorsevalueController extends BaseController
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
     * @page horsevalue
     * @name Horse Value
     */
    public function mainAction()
    {
    }
    
    /**
     * @page horsevalue
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }
    
    /**
     * @page horsevalue
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'horsevalue', model_id = 'id')
     */
    public function createAction()
    {
        ValueAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page horsevalue
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'horsevalue', model_id = 'id')
     */
    public function modifyAction()
    {
        ValueEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}