<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\TotalAddValidation;
use Imee\Controller\Validation\Operate\Play\Tarot\TotalEditValidation;
use Imee\Service\Operate\Play\Tarot\TotalService;

class TarottotalController extends BaseController
{
    /**
     * @var TotalService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new TotalService();
    }
    
    /**
     * @page tarottotal
     * @name Tarot Total
     */
    public function mainAction()
    {
    }
    
    /**
     * @page tarottotal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }
    
    /**
     * @page tarottotal
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'tarottotal', model_id = 'id')
     */
    public function createAction()
    {
        TotalAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page tarottotal
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'tarottotal', model_id = 'id')
     */
    public function modifyAction()
    {
        TotalEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}