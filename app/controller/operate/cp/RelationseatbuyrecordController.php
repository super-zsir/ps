<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Cp\RelationNumberPriceService;

class RelationseatbuyrecordController extends BaseController
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
     * @page relationseatbuyrecord
     * @name 关系席位购买记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page relationseatbuyrecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->_service->getRecordList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
}