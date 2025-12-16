<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingTotalAddValidation;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingTotalEditValidation;
use Imee\Service\Operate\Play\Fishing\FishingTotalService;

class FishingtotalController extends BaseController
{
    /** @var FishingTotalService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FishingTotalService();
    }
    
    /**
     * @page fishingtotal
     * @name Fishing Total
     */
    public function mainAction()
    {
    }
    
    /**
     * @page fishingtotal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
    
    /**
     * @page fishingtotal
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'fishingtotal', model_id = 'id')
     */
    public function createAction()
    {
        FishingTotalAddValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->create($this->params));
    }
    
    /**
     * @page fishingtotal
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'fishingtotal', model_id = 'id')
     */
    public function modifyAction()
    {
        FishingTotalEditValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->modify($this->params));
    }
}