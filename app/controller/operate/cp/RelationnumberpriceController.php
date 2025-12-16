<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Cp\RelationNumberPriceService;

class RelationnumberpriceController extends BaseController
{
    /**
     * @var RelationNumberPriceService $_service
     */
    private $_service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->_service = new RelationNumberPriceService();
    }
    
    /**
     * @page relationnumberprice
     * @name 关系数量价格调整
     */
    public function mainAction()
    {
    }
    
    /**
     * @page relationnumberprice
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->_service->getList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
     * @page relationnumberprice
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'relationnumberprice', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = $this->_service->modify($this->params);
        return $this->outputSuccess($data);
    }
}